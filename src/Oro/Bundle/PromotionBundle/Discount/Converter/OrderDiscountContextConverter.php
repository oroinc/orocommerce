<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\SaleBundle\Entity\Quote;

/**
 * Data converter that prepares discount context based on order entity to calculate discounts.
 */
class OrderDiscountContextConverter implements DiscountContextConverterInterface
{
    /**
     * @var OrderLineItemsToDiscountLineItemsConverter
     */
    protected $lineItemsConverter;

    /**
     * @var SubtotalProviderInterface
     */
    protected $lineItemsSubtotalProvider;

    /**
     * @param OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter
     * @param SubtotalProviderInterface $lineItemsSubtotalProvider
     */
    public function __construct(
        OrderLineItemsToDiscountLineItemsConverter $lineItemsConverter,
        SubtotalProviderInterface $lineItemsSubtotalProvider
    ) {
        $this->lineItemsConverter = $lineItemsConverter;
        $this->lineItemsSubtotalProvider = $lineItemsSubtotalProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($sourceEntity): DiscountContext
    {
        /** @var Order $sourceEntity */
        if (!$this->supports($sourceEntity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($sourceEntity))
            );
        }

        $discountContext = new DiscountContext();

        $subtotal = $this->lineItemsSubtotalProvider->getSubtotal($sourceEntity);
        $discountContext->setSubtotal($subtotal->getAmount());
        $shippingCost = $sourceEntity->getShippingCost();
        if ($shippingCost instanceof Price) {
            $discountContext->setShippingCost($shippingCost->getValue());
        }

        $discountLineItems = $this->lineItemsConverter->convert(
            $sourceEntity->getLineItems()->toArray()
        );
        $discountContext->setLineItems($discountLineItems);

        return $discountContext;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($sourceEntity): bool
    {
        return $sourceEntity instanceof Order && $sourceEntity->getSourceEntityClass() !== Quote::class;
    }
}
