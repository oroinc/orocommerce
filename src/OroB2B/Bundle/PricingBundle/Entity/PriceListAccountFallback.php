<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\AccountBundle\Entity\Account;

/**
 * @ORM\Table(name="orob2b_price_list_acc_fb")
 * @ORM\Entity()
 */
class PriceListAccountFallback extends PriceListFallback
{
    const CURRENT_ACCOUNT_ONLY = 0;
    const ACCOUNT_GROUP = 1;

    /** @var Account
     *
     * @ORM\OneToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $account;

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @param Account $account
     *
     * @return $this
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }
}
