<?php

namespace Oro\Bundle\CustomerBundle\Entity\Ownership;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;

trait FrontendCustomerUserAwareTrait
{
    use FrontendCustomerAwareTrait;

    /**
     * @var AccountUser
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $accountUser;

    /**
     * @return AccountUser|null
     */
    public function getCustomerUser()
    {
        return $this->accountUser;
    }

    /**
     * @param AccountUser|null $customerUser
     * @return $this
     */
    public function setCustomerUser(AccountUser $customerUser = null)
    {
        $this->accountUser = $customerUser;

        if ($customerUser && $customerUser->getCustomer()) {
            $this->setCustomer($customerUser->getCustomer());
        }

        return $this;
    }
}
