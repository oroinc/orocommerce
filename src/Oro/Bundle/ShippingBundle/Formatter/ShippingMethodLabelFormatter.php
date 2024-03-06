<?php

namespace Oro\Bundle\ShippingBundle\Formatter;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
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
    private ShippingMethodOrganizationProvider $organizationProvider;

    public function __construct(
        ShippingMethodProviderInterface $shippingMethodProvider,
        ShippingMethodOrganizationProvider $organizationProvider
    ) {
        $this->shippingMethodProvider = $shippingMethodProvider;
        $this->organizationProvider = $organizationProvider;
    }

    public function formatShippingMethodLabel(
        ?string $shippingMethodName,
        ?Organization $organization = null
    ): string {
        return $this->doFormatShippingMethodLabel($this->getShippingMethod($shippingMethodName, $organization));
    }

    public function formatShippingMethodTypeLabel(
        ?string $shippingMethodName,
        ?string $shippingTypeName,
        ?Organization $organization = null
    ): string {
        if (!$shippingTypeName) {
            return self::EMPTY_STRING;
        }

        return $this->doFormatShippingMethodTypeLabel(
            $this->getShippingMethod($shippingMethodName, $organization),
            $shippingTypeName
        );
    }

    public function formatShippingMethodWithTypeLabel(
        ?string $shippingMethodName,
        ?string $shippingTypeName,
        ?Organization $organization = null
    ): string {
        $shippingMethod = $this->getShippingMethod($shippingMethodName, $organization);
        $methodLabel = $this->doFormatShippingMethodLabel($shippingMethod);
        $methodTypeLabel = $shippingTypeName
            ? $this->doFormatShippingMethodTypeLabel($shippingMethod, $shippingTypeName)
            : self::EMPTY_STRING;

        return self::EMPTY_STRING === $methodLabel
            ? $methodTypeLabel
            : $methodLabel . self::DELIMITER . $methodTypeLabel;
    }

    private function getShippingMethod(
        ?string $shippingMethodName,
        ?Organization $organization
    ): ?ShippingMethodInterface {
        if (!$shippingMethodName) {
            return null;
        }

        if (null === $organization) {
            return $this->shippingMethodProvider->getShippingMethod($shippingMethodName);
        }

        $previousOrganization = $this->organizationProvider->getOrganization();
        $this->organizationProvider->setOrganization($organization);
        try {
            return $this->shippingMethodProvider->getShippingMethod($shippingMethodName);
        } finally {
            $this->organizationProvider->setOrganization($previousOrganization);
        }
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
