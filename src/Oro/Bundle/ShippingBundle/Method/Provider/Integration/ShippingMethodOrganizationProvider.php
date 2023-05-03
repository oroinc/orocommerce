<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Provides an access to an organization for which shipping methods should be provided.
 * In some cases, e.g. when Multi Shipping method is used, it is required to get shipping methods
 * for a product's organization instead of for an user organization and as result the organization
 * should be passes from a service that knows about a product to a service that provides shipping methods.
 * This service was introduced to avoid continually passing an organization down the chain of different services.
 */
class ShippingMethodOrganizationProvider
{
    private ?Organization $organization = null;

    public function setOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getOrganizationId(): ?int
    {
        return $this->getOrganization()?->getId();
    }
}
