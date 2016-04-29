<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Ownership;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

trait FrontendAccountUserAwareTrait
{
    use FrontendAccountAwareTrait;

    /**
     * @var AccountUser
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountUser")
     * @ORM\JoinColumn(name="account_user_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $accountUser;

    /**
     * @return AccountUser|null
     */
    public function getAccountUser()
    {
        return $this->accountUser;
    }

    /**
     * @param AccountUser|null $accountUser
     * @return $this
     */
    public function setAccountUser(AccountUser $accountUser = null)
    {
        $this->accountUser = $accountUser;

        if ($accountUser && $accountUser->getAccount()) {
            $this->setAccount($accountUser->getAccount());
        }

        return $this;
    }
}
