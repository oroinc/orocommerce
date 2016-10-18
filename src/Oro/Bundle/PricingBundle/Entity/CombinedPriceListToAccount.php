<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CustomerBundle\Entity\Account;

/**
 * @ORM\Table(
 *      name="oro_cmb_price_list_to_acc",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_cpl_to_acc_ws_unq", columns={
 *              "account_id",
 *              "website_id"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToAccountRepository")
 */
class CombinedPriceListToAccount extends BaseCombinedPriceListRelation
{
    /**
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\Account")
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
