<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AccountBundle\Entity\Account;

/**
 * @ORM\Table(name="orob2b_price_list_to_account")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository")
 */
class PriceListToAccount extends BasePriceListRelation
{
    /**
     * @var Account
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
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
     * @return $this
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;

        return $this;
    }
}
