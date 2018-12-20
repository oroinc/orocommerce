<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

/**
 * Extends CustomerUser with acceptedConsents property accessors
 */
class CustomerUserStub extends CustomerUser
{
    /** @var Collection */
    private $acceptedConsents;

    /**
     * @return Collection|null
     */
    public function getAcceptedConsents()
    {
        return $this->acceptedConsents;
    }

    /**
     * @param Collection $acceptedConsents
     * @return CustomerUserStub
     */
    public function setAcceptedConsents(Collection $acceptedConsents): self
    {
        $this->acceptedConsents = $acceptedConsents;

        return $this;
    }
}
