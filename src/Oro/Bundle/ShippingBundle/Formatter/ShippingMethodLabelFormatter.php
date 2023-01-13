<?php

namespace Oro\Bundle\ShippingBundle\Formatter;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Provides methods to format shipping method label.
 */
class ShippingMethodLabelFormatter
{
    private const DELIMITER = ', ';
    private const EMPTY_STRING = '';

    private ShippingMethodProviderInterface $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    public function formatShippingMethodLabel(?string $shippingMethodName): string
    {
        return $this->doFormatShippingMethodLabel($this->getShippingMethod($shippingMethodName));
    }

    public function formatShippingMethodTypeLabel(?string $shippingMethodName, ?string $shippingTypeName): string
    {
        if (!$shippingTypeName) {
            return self::EMPTY_STRING;
        }

        return $this->doFormatShippingMethodTypeLabel(
            $this->getShippingMethod($shippingMethodName),
            $shippingTypeName
        );
    }

    public function formatShippingMethodWithTypeLabel(?string $shippingMethodName, ?string $shippingTypeName): string
    {
        $shippingMethod = $this->getShippingMethod($shippingMethodName);
        $methodLabel = $this->doFormatShippingMethodLabel($shippingMethod);
        $methodTypeLabel = $shippingTypeName
            ? $this->doFormatShippingMethodTypeLabel($shippingMethod, $shippingTypeName)
            : self::EMPTY_STRING;

        return self::EMPTY_STRING === $methodLabel
            ? $methodTypeLabel
            : $methodLabel . self::DELIMITER . $methodTypeLabel;
    }

    private function getShippingMethod(?string $shippingMethodName): ?ShippingMethodInterface
    {
        if (!$shippingMethodName) {
            return null;
        }

        return $this->shippingMethodProvider->getShippingMethod($shippingMethodName);
    }

    private function doFormatShippingMethodLabel(?ShippingMethodInterface $shippingMethod): string
    {
        if (null === $shippingMethod || !$shippingMethod->isGrouped()) {
            return self::EMPTY_STRING;
        }

        return $shippingMethod->getLabel();
    }

    private function doFormatShippingMethodTypeLabel(
        ?ShippingMethodInterface $shippingMethod,
        string $shippingTypeName
    ): string {
        if (null === $shippingMethod) {
            return self::EMPTY_STRING;
        }

        $shippingMethodType = $shippingMethod->getType($shippingTypeName);
        if (null === $shippingMethodType) {
            return self::EMPTY_STRING;
        }

        return $shippingMethodType->getLabel();
    }
}
