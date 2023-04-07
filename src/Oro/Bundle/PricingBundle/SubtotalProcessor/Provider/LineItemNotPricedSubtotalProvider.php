<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemsHolderCurrencyProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemsHolderPricesProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for line items without prices.
 *
 * Expression for regular line items:
 *  lineItems.SUM(
 *      ROUND(lineItem.price * lineItem.qty)
 *  )
 *
 * Expression for line items with kit item line items:
 *  lineItems.SUM(
 *      ROUND(
 *          (
 *              lineItem.price + lineItem.kitItemLineItems.SUM(
 *                  ROUND(kitItemLineItem.price * kitItemLineItem.qty)
 *              )
 *          ) * lineItem.qty
 *      )
 *  )
 */
class LineItemNotPricedSubtotalProvider implements SubtotalProviderInterface
{
    public const TYPE = 'subtotal';
    public const TYPE_LINE_ITEM = 'line_item_subtotal';
    public const LABEL = 'oro.pricing.subtotals.not_priced_subtotal.label';

    protected TranslatorInterface $translator;

    protected RoundingServiceInterface $rounding;

    protected ProductLineItemsHolderCurrencyProvider $productLineItemsHolderCurrencyProvider;

    protected ProductLineItemsHolderPricesProvider $productLineItemsHolderPricesProvider;

    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        ProductLineItemsHolderCurrencyProvider $productLineItemsHolderCurrencyProvider,
        ProductLineItemsHolderPricesProvider $productLineItemsHolderPricesProvider
    ) {
        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->productLineItemsHolderCurrencyProvider = $productLineItemsHolderCurrencyProvider;
        $this->productLineItemsHolderPricesProvider = $productLineItemsHolderPricesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($entity)
    {
        return $entity instanceof LineItemsNotPricedAwareInterface;
    }

    /**
     * Get line items subtotal for current user currency
     *
     * @param LineItemsNotPricedAwareInterface|CustomerOwnerAwareInterface|WebsiteAwareInterface $entity
     *
     * @return Subtotal
     */
    public function getSubtotal($entity)
    {
        $currency = $this->productLineItemsHolderCurrencyProvider->getCurrencyForLineItemsHolder($entity);

        return $this->getSubtotalByCurrency($entity, $currency);
    }

    /**
     * @param LineItemsNotPricedAwareInterface|CustomerOwnerAwareInterface|WebsiteAwareInterface $entity
     * @param string $currency
     *
     * @return Subtotal|null
     */
    public function getSubtotalByCurrency($entity, $currency): ?Subtotal
    {
        if (!$entity instanceof LineItemsNotPricedAwareInterface) {
            return null;
        }

        $subtotalAmount = BigDecimal::of(0);
        [$prices, $productPriceCriteria] = $this->productLineItemsHolderPricesProvider
            ->getMatchedPricesForLineItemsHolder($entity, $currency);
        $subtotal = $this->createSubtotal();

        if ($prices) {
            foreach ($entity->getLineItems() as $lineItem) {
                $kitItemLineItemSubtotals = new \WeakMap();
                if ($this->hasKitItemLineItems($lineItem)) {
                    foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                        $kitItemLineItemSubtotal = $this->createLineItemSubtotal(
                            $kitItemLineItem,
                            $prices,
                            $productPriceCriteria
                        );

                        if ($kitItemLineItemSubtotal !== null) {
                            $kitItemLineItemSubtotals[$kitItemLineItem] = $kitItemLineItemSubtotal;
                        } elseif (!$kitItemLineItem->getKitItem()?->isOptional()) {
                            continue 2;
                        }
                    }
                }

                $lineItemSubtotal = $this->createLineItemSubtotal(
                    $lineItem,
                    $prices,
                    $productPriceCriteria,
                    $kitItemLineItemSubtotals
                );

                if ($lineItemSubtotal !== null) {
                    $subtotalAmount = $subtotalAmount->plus($this->rounding->round($lineItemSubtotal->getAmount()));
                    $subtotal->addLineItemSubtotal($lineItem, $lineItemSubtotal);
                }
            }
        }

        $subtotal->setAmount($subtotalAmount->toFloat());
        $subtotal->setVisible($subtotal->getAmount() !== 0.0);
        $subtotal->setCurrency($currency);

        return $subtotal;
    }

    protected function hasKitItemLineItems(ProductLineItemInterface $lineItem): bool
    {
        return $lineItem instanceof ProductKitItemLineItemsAwareInterface && $lineItem->getKitItemLineItems();
    }

    /**
     * @param ProductLineItemInterface $lineItem
     * @param array<string,Price> $prices
     * @param array<string,ProductPriceCriteria> $productPriceCriteria
     * @param \WeakMap<ProductLineItemInterface,Subtotal>|null $lineItemSubtotals
     *
     * @return Subtotal|null
     */
    protected function createLineItemSubtotal(
        ProductLineItemInterface $lineItem,
        array $prices,
        array $productPriceCriteria,
        \WeakMap $lineItemSubtotals = null
    ): ?Subtotal {
        $lineItemPriceCriterion = $productPriceCriteria[spl_object_hash($lineItem)] ?? null;
        $lineItemPrice = $prices[$lineItemPriceCriterion?->getIdentifier()] ?? null;

        if ($lineItemPrice === null && !$lineItemSubtotals?->count()) {
            return null;
        }

        $subtotal = (new Subtotal())
            ->setType(self::TYPE_LINE_ITEM)
            ->setVisible(false)
            ->setRemovable(false);

        $priceAmount = BigDecimal::of((float)$lineItemPrice?->getValue());

        if ($lineItemSubtotals) {
            foreach ($lineItemSubtotals as $eachLineItem => $eachSubtotal) {
                $priceAmount = $priceAmount->plus($eachSubtotal->getAmount());
                $subtotal->addLineItemSubtotal($eachLineItem, $eachSubtotal);
            }
        }

        $quantity = (float)$lineItemPriceCriterion?->getQuantity();
        $subtotalAmount = $priceAmount->multipliedBy($quantity);

        $currency = $lineItemPriceCriterion?->getCurrency();

        $subtotal
            ->setPrice(Price::create($priceAmount->toFloat(), $currency))
            ->setQuantity($quantity)
            ->setAmount($subtotalAmount->toFloat())
            ->setCurrency($currency);

        return $subtotal;
    }

    /**
     * @return Subtotal
     */
    protected function createSubtotal()
    {
        $subtotal = new Subtotal();
        $subtotal->setLabel($this->translator->trans(self::LABEL));
        $subtotal->setVisible(false);
        $subtotal->setType(self::TYPE);
        $subtotal->setRemovable(false);

        return $subtotal;
    }
}
