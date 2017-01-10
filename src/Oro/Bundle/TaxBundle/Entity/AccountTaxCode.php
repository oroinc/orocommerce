<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\TaxBundle\Entity\Repository\AccountTaxCodeRepository")
 * @ORM\Table(name="oro_tax_customer_tax_code")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="oro_tax_account_tax_code_index",
 *      routeView="oro_tax_account_tax_code_view",
 *      routeUpdate="oro_tax_account_tax_code_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-list-alt"
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
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer")
     * @ORM\JoinTable(
     *      name="oro_tax_cus_tax_code_cus",
     *      joinColumns={
     *          @ORM\JoinColumn(name="customer_tax_code_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     *
     * @var Customer[]|Collection
     */
    protected $accounts;

    /**
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerGroup")
     * @ORM\JoinTable(
     *      name="oro_tax_cus_grp_tc_cus_grp",
     *      joinColumns={
     *          @ORM\JoinColumn(name="customer_group_tax_code_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="customer_group_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     *
     * @var CustomerGroup[]|Collection
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
     * @param Customer $account
     * @return $this
     */
    public function addAccount(Customer $account)
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts->add($account);
        }

        return $this;
    }

    /**
     * Remove account
     *
     * @param Customer $account
     * @return $this
     */
    public function removeAccount(Customer $account)
    {
        if ($this->accounts->contains($account)) {
            $this->accounts->removeElement($account);
        }

        return $this;
    }

    /**
     * Get accounts
     *
     * @return Customer[]|Collection
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * Add accountGroup
     *
     * @param CustomerGroup $accountGroup
     * @return $this
     */
    public function addAccountGroup(CustomerGroup $accountGroup)
    {
        if (!$this->accountGroups->contains($accountGroup)) {
            $this->accountGroups->add($accountGroup);
        }

        return $this;
    }

    /**
     * Remove accountGroup
     *
     * @param CustomerGroup $accountGroup
     * @return $this
     */
    public function removeAccountGroup(CustomerGroup $accountGroup)
    {
        if ($this->accountGroups->contains($accountGroup)) {
            $this->accountGroups->removeElement($accountGroup);
        }

        return $this;
    }

    /**
     * Get accountGroups
     *
     * @return CustomerGroup[]|Collection
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
