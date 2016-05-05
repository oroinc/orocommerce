<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\AccountBundle\Entity\Account;

/**
 * @ORM\Table(
 *      name="orob2b_cmb_price_list_to_acc",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orob2b_cpl_to_acc_ws_unq", columns={
 *              "account_id",
 *              "website_id"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToAccountRepository")
 */
class CombinedPriceListToAccount extends BaseCombinedPriceListRelation
{
    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
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
