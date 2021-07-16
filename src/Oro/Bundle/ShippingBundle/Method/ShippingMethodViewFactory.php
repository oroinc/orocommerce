<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;

class ShippingMethodViewFactory
{
    /**
     * @var ShippingMethodProviderInterface
     */
    private $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * @param string $shippingMethodId
     * @param string $label
     * @param bool $isGrouped
     * @param int $sortOrder
     *
     * @return array
     */
    public function createMethodView($shippingMethodId, $label, $isGrouped, $sortOrder)
    {
        return [
            'identifier' => $shippingMethodId,
            'isGrouped' => $isGrouped,
            'label' => $label,
            'sortOrder' => $sortOrder,
        ];
    }

    /**
     * @param string $shippingMethodTypeId
     * @param string $label
     * @param int $sortOrder
     * @param Price $price
     *
     * @return array
     */
    public function createMethodTypeView($shippingMethodTypeId, $label, $sortOrder, Price $price)
    {
        return [
            'identifier' => $shippingMethodTypeId,
            'label' => $label,
            'sortOrder' => $sortOrder,
            'price' => $price,
        ];
    }

    /**
     * @param string $shippingMethodId
     *
     * @return array|null
     */
    public function createMethodViewByShippingMethod($shippingMethodId)
    {
        $method = $this->shippingMethodProvider->getShippingMethod($shippingMethodId);

        if (!$method) {
            return null;
        }

        return $this->createMethodView(
            $shippingMethodId,
            $method->getLabel(),
            $method->isGrouped(),
            $method->getSortOrder()
        );
    }

    /**
     * @param string $shippingMethodId
     * @param string $shippingMethodTypeId
     * @param Price $price
     *
     * @return array|null
     */
    public function createMethodTypeViewByShippingMethodAndPrice($shippingMethodId, $shippingMethodTypeId, Price $price)
    {
        $method = $this->shippingMethodProvider->getShippingMethod($shippingMethodId);

        if (!$method) {
            return null;
        }

        $type = $method->getType($shippingMethodTypeId);

        if (!$type) {
            return null;
        }

        return $this->createMethodTypeView($shippingMethodTypeId, $type->getLabel(), $type->getSortOrder(), $price);
    }
}
