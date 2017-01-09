<?php

namespace Oro\Bundle\CustomerBundle\Entity\Ownership;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

/**
 * @deprecated Use FrontendCustomerUserAwareTrait
 */
trait FrontendAccountUserAwareTrait
{
    use FrontendAccountAwareTrait;

    /**
     * @var CustomerUser
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUser")
     * @ORM\JoinColumn(name="customer_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $accountUser;

    /**
     * @return CustomerUser|null
     */
    public function getAccountUser()
    {
        return $this->accountUser;
    }

    /**
     * @param CustomerUser|null $accountUser
     * @return $this
     */
    public function setAccountUser(CustomerUser $accountUser = null)
    {
        $this->accountUser = $accountUser;

        if ($accountUser && $accountUser->getAccount()) {
            $this->setAccount($accountUser->getAccount());
        }

        return $this;
    }
}
