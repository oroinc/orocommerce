<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\TaxBundle\Model\TaxCodeInterface;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository")
 * @ORM\Table(name="orob2b_tax_account_tax_code")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="orob2b_tax_account_tax_code_index",
 *      routeView="orob2b_tax_account_tax_code_view",
 *      routeUpdate="orob2b_tax_account_tax_code_update",
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
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinTable(
     *      name="orob2b_tax_acc_tax_code_acc",
     *      joinColumns={
     *          @ORM\JoinColumn(name="account_tax_code_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     *
     * @var Account[]|Collection
     */
    protected $accounts;

    /**
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountGroup")
     * @ORM\JoinTable(
     *      name="orob2b_tax_acc_grp_tc_acc_grp",
     *      joinColumns={
     *          @ORM\JoinColumn(name="account_group_tax_code_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="account_group_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     *
     * @var AccountGroup[]|Collection
     */
    protected $accountGroups;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
        $this->accountGroups = new ArrayCollection();
    }

    /**
     * Add account
     *
     * @param Account $account
     * @return $this
     */
    public function addAccount(Account $account)
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts->add($account);
        }

        return $this;
    }

    /**
     * Remove account
     *
     * @param Account $account
     * @return $this
     */
    public function removeAccount(Account $account)
    {
        if ($this->accounts->contains($account)) {
            $this->accounts->removeElement($account);
        }

        return $this;
    }

    /**
     * Get accounts
     *
     * @return Account[]|Collection
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * Add accountGroup
     *
     * @param AccountGroup $accountGroup
     * @return $this
     */
    public function addAccountGroup(AccountGroup $accountGroup)
    {
        if (!$this->accountGroups->contains($accountGroup)) {
            $this->accountGroups->add($accountGroup);
        }

        return $this;
    }

    /**
     * Remove accountGroup
     *
     * @param AccountGroup $accountGroup
     * @return $this
     */
    public function removeAccountGroup(AccountGroup $accountGroup)
    {
        if ($this->accountGroups->contains($accountGroup)) {
            $this->accountGroups->removeElement($accountGroup);
        }

        return $this;
    }

    /**
     * Get accountGroups
     *
     * @return AccountGroup[]|Collection
     */
    public function getAccountGroups()
    {
        return $this->accountGroups;
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return TaxCodeInterface::TYPE_ACCOUNT;
    }
}
