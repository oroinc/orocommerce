<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\AccountBundle\Entity\Account;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_tax_account_tax_code")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="orob2b_tax_account_tax_code_index",
 *      routeView="orob2b_tax_account_tax_code_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-list-alt"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
class AccountTaxCode extends AbstractTaxCode
{
    /**
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id")
     *
     * @var Account
     */
    protected $account;

    /**
     * Set account
     *
     * @param Account $account
     * @return $this
     */
    public function setAccount(Account $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }
}
