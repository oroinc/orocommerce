<?php

namespace Oro\Bundle\CustomerBundle\Entity\Ownership;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

trait FrontendCustomerUserAwareTrait
{
    use FrontendCustomerAwareTrait;

    /**
     * @var CustomerUser
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $accountUser;

    /**
     * @return CustomerUser|null
     */
    public function getCustomerUser()
    {
        return $this->accountUser;
    }

    /**
     * @param CustomerUser|null $customerUser
     * @return $this
     */
    public function setCustomerUser(CustomerUser $customerUser = null)
    {
        $this->accountUser = $customerUser;

        if ($customerUser && $customerUser->getCustomer()) {
            $this->setCustomer($customerUser->getCustomer());
        }

        return $this;
    }
}
