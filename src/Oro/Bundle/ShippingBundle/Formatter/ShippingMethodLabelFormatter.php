<?php

namespace Oro\Bundle\ShippingBundle\Formatter;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class ShippingMethodLabelFormatter
{
    /** @internal */
    const DELIMITER = ', ';

    /**
     * @var ShippingMethodProviderInterface
     */
    protected $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * @param string $shippingMethodName
     * @return string
     */
    public function formatShippingMethodLabel($shippingMethodName)
    {
        $shippingMethod = $this->shippingMethodProvider->getShippingMethod($shippingMethodName);

        if (!$shippingMethod || !$shippingMethod->isGrouped()) {
            return '';
        }

        return $shippingMethod->getLabel();
    }

    /**
     * @param string $shippingMethodName
     * @param string $shippingTypeName
     * @return string
     */
    public function formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName)
    {
        $shippingMethod = $this->shippingMethodProvider->getShippingMethod($shippingMethodName);

        if (!$shippingMethod) {
            return '';
        }

        $shippingMethodType = $shippingMethod->getType($shippingTypeName);

        if (!$shippingMethodType) {
            return '';
        }

        return $shippingMethodType->getLabel();
    }

    /**
     * @param string $shippingMethodName
     * @param string $shippingTypeName
     * @return string
     */
    public function formatShippingMethodWithTypeLabel($shippingMethodName, $shippingTypeName)
    {
        $methodLabel = $this->formatShippingMethodLabel($shippingMethodName);
        $methodTypeLabel = $this->formatShippingMethodTypeLabel($shippingMethodName, $shippingTypeName);

        if ($methodLabel === '') {
            return $methodTypeLabel;
        }

        return $methodLabel . self::DELIMITER . $methodTypeLabel;
    }
}
