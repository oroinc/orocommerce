<?php

namespace Oro\Bundle\PromotionBundle\Context;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Discount\Exception\UnsupportedSourceEntityException;

/**
 * Prepares promotion context data based on checkout entity to filter applicable promotions.
 */
class CheckoutShippingContextDataConverter implements ContextDataConverterInterface
{
    /** @var ContextDataConverterInterface */
    private $checkoutContextDataConverter;

    public function __construct(ContextDataConverterInterface $checkoutContextDataConverter)
    {
        $this->checkoutContextDataConverter = $checkoutContextDataConverter;
    }

    /**
     * @param Checkout $entity
     * {@inheritdoc}
     */
    public function getContextData($entity): array
    {
        if (!$this->supports($entity)) {
            throw new UnsupportedSourceEntityException(
                sprintf('Source entity "%s" is not supported.', get_class($entity))
            );
        }

        $shippingMethod = $entity->getShippingMethod();
        $shippingMethodType = (string) $entity->getShippingMethodType();
        $shippingCost = $entity->getShippingCost();

        $entity->setShippingMethod(null);
        $entity->setShippingMethodType(null);
        $entity->setShippingCost(new Price());

        $contextData = $this->checkoutContextDataConverter->getContextData($entity);

        $contextData[self::SHIPPING_METHOD] = $shippingMethod;
        $contextData[self::SHIPPING_METHOD_TYPE] = $shippingMethodType;
        $contextData[self::SHIPPING_COST] = $shippingCost;

        return $contextData;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($entity): bool
    {
        return $this->checkoutContextDataConverter->supports($entity);
    }
}
