<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
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
class LineItemNotPricedSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    public const TYPE = 'subtotal';
    public const LABEL = 'oro.pricing.subtotals.not_priced_subtotal.label';
    public const EXTRA_DATA_PRICE = 'price';
    public const EXTRA_DATA_SUBTOTAL = 'subtotal';

    protected TranslatorInterface $translator;

    protected RoundingServiceInterface $rounding;

    protected ProductPriceProviderInterface $productPriceProvider;

    protected ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    public function __construct(
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        ProductPriceProviderInterface $productPriceProvider,
        SubtotalProviderConstructorArguments $arguments,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        parent::__construct($arguments);

        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
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
        return $this->getSubtotalByCurrency($entity, $this->getBaseCurrency($entity));
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
        $priceCriteria = $this->prepareProductsPriceCriteria($entity, $currency);
        $extraData = [];
        if ($priceCriteria) {
            $searchScope = $this->priceScopeCriteriaFactory->createByContext($entity);
            $prices = $this->productPriceProvider->getMatchedPrices($priceCriteria, $searchScope);
            foreach ($entity->getLineItems() as $lineItem) {
                $initialLineItemSubtotalAmount = null;
                if ($lineItem instanceof ProductKitItemLineItemsAwareInterface && $lineItem->getKitItemLineItems()) {
                    foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                        if ($initialLineItemSubtotalAmount === null) {
                            $initialLineItemSubtotalAmount = BigDecimal::of(0);
                        }
                        $kitItemLineItemSubtotalAmount = $this->getLineItemSubtotalAmount(
                            $kitItemLineItem,
                            $prices,
                            $priceCriteria,
                            $extraData
                        );
                        $initialLineItemSubtotalAmount = $initialLineItemSubtotalAmount
                            ->plus($kitItemLineItemSubtotalAmount);
                    }
                }

                $lineItemSubtotalAmount = $this->getLineItemSubtotalAmount(
                    $lineItem,
                    $prices,
                    $priceCriteria,
                    $extraData,
                    $initialLineItemSubtotalAmount
                );
                $subtotalAmount = $subtotalAmount->plus($lineItemSubtotalAmount);
            }
        }

        $subtotal = $this->createSubtotal();
        $subtotal->setData($extraData);
        $subtotal->setAmount($subtotalAmount->toFloat());
        $subtotal->setVisible($subtotal->getAmount() !== 0.0);
        $subtotal->setCurrency($currency);

        return $subtotal;
    }

    /**
     * @param object $lineItem
     * @param array<string,Price> $prices
     * @param array<string,ProductPriceCriteria> $priceCriteria
     * @param array $extraData
     * @param BigDecimal|null $initialAmount
     *
     * @return float|int
     */
    private function getLineItemSubtotalAmount(
        object $lineItem,
        array $prices,
        array $priceCriteria,
        array &$extraData,
        ?BigDecimal $initialAmount = null
    ): float|int {
        $lineItemHash = spl_object_hash($lineItem);
        $lineItemPriceCriterion = $priceCriteria[$lineItemHash] ?? null;
        $lineItemPrice = $prices[$lineItemPriceCriterion?->getIdentifier()] ?? null;

        if ($lineItemPrice !== null || $initialAmount !== null) {
            $priceAmount = ($initialAmount ?? BigDecimal::of(0))
                ->plus((float)$lineItemPrice?->getValue());

            $subtotalAmount = $priceAmount
                ->multipliedBy((float)$lineItemPriceCriterion?->getQuantity());

            $extraData[$lineItemHash] = [
                self::EXTRA_DATA_PRICE => $priceAmount->toFloat(),
                self::EXTRA_DATA_SUBTOTAL => $subtotalAmount->toFloat(),
            ];

            return $this->rounding->round($subtotalAmount->toFloat());
        }

        return 0.0;
    }

    /**
     * @param LineItemsNotPricedAwareInterface|CustomerOwnerAwareInterface|WebsiteAwareInterface $entity
     * @param string $currency
     * @return ProductPriceCriteria[]
     */
    protected function prepareProductsPriceCriteria($entity, $currency)
    {
        $productsPriceCriteria = [];
        foreach ($this->getLineItems($entity) as $lineItem) {
            if ($lineItem instanceof ProductHolderInterface
                && $lineItem instanceof ProductUnitHolderInterface
                && $lineItem instanceof QuantityAwareInterface
            ) {
                $hasProduct = $lineItem->getProduct() && $lineItem->getProduct()->getId();
                $hasProductUnitCode = $lineItem->getProductUnit() && $lineItem->getProductUnit()->getCode();
                if ($hasProduct && $hasProductUnitCode) {
                    $quantity = (float)$lineItem->getQuantity();
                    $criteria = new ProductPriceCriteria(
                        $lineItem->getProduct(),
                        $lineItem->getProductUnit(),
                        $quantity,
                        $currency
                    );
                    $productsPriceCriteria[spl_object_hash($lineItem)] = $criteria;
                }
            }
        }

        return $productsPriceCriteria;
    }

    private function getLineItems(LineItemsNotPricedAwareInterface $entity): \Generator
    {
        foreach ($entity->getLineItems() as $lineItem) {
            if ($lineItem instanceof ProductKitItemLineItemsAwareInterface && $lineItem->getKitItemLineItems()) {
                foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
                    yield $kitItemLineItem;
                }
            }

            yield $lineItem;
        }
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
