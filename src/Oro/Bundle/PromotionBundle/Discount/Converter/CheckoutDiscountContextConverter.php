<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;

/**
 * Data converter that prepares discount context based on checkout entity to calculate discounts.
 */
class CheckoutDiscountContextConverter implements DiscountContextConverterInterface
{
    /**
     * @var CheckoutToOrderConverter
     */
    private $checkoutToOrderConverter;
    /**
     * @var DiscountContextConverterInterface
     */
    private $orderDiscountContextConverter;

    /**
     * @param CheckoutToOrderConverter $checkoutToOrderConverter
     * @param DiscountContextConverterInterface $orderDiscountContextConverter
     */
    public function __construct(
        CheckoutToOrderConverter $checkoutToOrderConverter,
        DiscountContextConverterInterface $orderDiscountContextConverter
    ) {
        $this->checkoutToOrderConverter = $checkoutToOrderConverter;
        $this->orderDiscountContextConverter = $orderDiscountContextConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($sourceEntity): DiscountContext
    {
        /** @var Checkout $sourceEntity */
        if (!$this->supports($sourceEntity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($sourceEntity))
            );
        }
        $order = $this->checkoutToOrderConverter->getOrder($sourceEntity);

        return $this->orderDiscountContextConverter->convert($order);
    }

    /**
     * {@inheritdoc}
     */
    public function supports($sourceEntity): bool
    {
        return $sourceEntity instanceof Checkout && !$sourceEntity->getSourceEntity() instanceof QuoteDemand;
    }
}
