<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

/**
 * Extends CustomerUser with acceptedConsents property accessors
 */
class CustomerUserStub extends CustomerUser
{
    /** @var ArrayCollection */
    private $acceptedConsents;

    /**
     * @return ArrayCollection
     */
    public function getAcceptedConsents(): ArrayCollection
    {
        return $this->acceptedConsents;
    }

    /**
     * @param ArrayCollection $acceptedConsents
     * @return CustomerUserStub
     */
    public function setAcceptedConsents(ArrayCollection $acceptedConsents): self
    {
        $this->acceptedConsents = $acceptedConsents;

        return $this;
    }
}
