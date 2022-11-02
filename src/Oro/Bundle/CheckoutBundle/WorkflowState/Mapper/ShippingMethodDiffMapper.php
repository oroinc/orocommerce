<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;

class ShippingMethodDiffMapper implements CheckoutStateDiffMapperInterface
{
    const DATA_NAME = 'shipping_method';

    /**
     * @var CheckoutShippingMethodsProviderInterface
     */
    private $checkoutShippingMethodsProvider;

    public function __construct(CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider)
    {
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function isEntitySupported($entity)
    {
        return is_object($entity) && $entity instanceof Checkout;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::DATA_NAME;
    }

    /**
     * @param Checkout $checkout
     * @return string
     */
    public function getCurrentState($checkout)
    {
        $shippingMethod = $checkout->getShippingMethod();
        if ($shippingMethod) {
            $shippingMethodType = $checkout->getShippingMethodType();
            $price = $this->checkoutShippingMethodsProvider->getPrice($checkout);

            if ($price) {
                return md5(serialize([
                    'method' => $shippingMethod,
                    'type' => $shippingMethodType,
                    'price' => $price,
                ]));
            }
        }

        return '';
    }

    /** {@inheritdoc} */
    public function isStatesEqual($entity, $state1, $state2)
    {
        if (($state2 === '' && $state1 !== '') || !$entity->getShippingMethod()) {
            return true;
        }

        return $state1 === $state2;
    }
}
