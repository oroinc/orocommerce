<?php

namespace Oro\Bundle\PricingBundle\SubtotalProcessor\Provider;

use Brick\Math\BigDecimal;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsNotPricedAwareInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Subtotal provider for line items without prices.
 *
 * Pseudo-expression of a subtotal:
 *  lineItems.SUM(lineItem.subtotal)
 */
class LineItemNotPricedSubtotalProvider extends AbstractSubtotalProvider implements SubtotalProviderInterface
{
    public const TYPE = 'subtotal';
    public const LABEL = 'oro.pricing.subtotals.not_priced_subtotal.label';

    private TranslatorInterface $translator;

    private ProductLineItemPriceProviderInterface $productLineItemsPriceProvider;

    public function __construct(
        SubtotalProviderConstructorArguments $arguments,
        TranslatorInterface $translator,
        ProductLineItemPriceProviderInterface $productLineItemsPriceProvider
    ) {
        parent::__construct($arguments);

        $this->translator = $translator;
        $this->productLineItemsPriceProvider = $productLineItemsPriceProvider;
    }

    #[\Override]
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
    #[\Override]
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
        $productLineItemsPrices = $this->productLineItemsPriceProvider
            ->getProductLineItemsPricesForLineItemsHolder($entity, $currency);

        foreach ($productLineItemsPrices as $productLineItemPrice) {
            $subtotalAmount = $subtotalAmount->plus($productLineItemPrice->getSubtotal());
        }

        $subtotal = $this->createSubtotal();
        $subtotal->setAmount($subtotalAmount->toFloat());
        $subtotal->setVisible($subtotal->getAmount() !== 0.0);
        $subtotal->setCurrency($currency);

        return $subtotal;
    }

    protected function createSubtotal(): Subtotal
    {
        $subtotal = new Subtotal();
        $subtotal->setLabel($this->translator->trans(self::LABEL));
        $subtotal->setVisible(false);
        $subtotal->setType(self::TYPE);
        $subtotal->setRemovable(false);

        return $subtotal;
    }
}
