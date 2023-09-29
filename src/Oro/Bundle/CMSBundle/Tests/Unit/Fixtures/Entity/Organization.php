<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization as BaseOrganization;

/**
 * Organization entity stub for testing purposes
 */
class Organization extends BaseOrganization
{
    protected bool $isGlobal = true;

    public function getIsGlobal(): bool
    {
        return $this->isGlobal;
    }

    public function setIsGlobal(bool $isGlobal): self
    {
        $this->isGlobal = $isGlobal;

        return $this;
    }
}
