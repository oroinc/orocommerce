<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Stub;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class ConsentAcceptanceStub extends ConsentAcceptance
{
    /** @var CustomerUser */
    private $customerUser;

    /**
     * @return CustomerUser
     */
    public function getCustomerUser()
    {
        return $this->customerUser;
    }

    /**
     * @param CustomerUser $customerUser
     * @return $this
     */
    public function setCustomerUser(CustomerUser $customerUser)
    {
        $this->customerUser = $customerUser;

        return $this;
    }
}
