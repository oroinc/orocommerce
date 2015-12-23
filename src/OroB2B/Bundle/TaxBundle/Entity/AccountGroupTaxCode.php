<?php

namespace OroB2B\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

/**
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\TaxBundle\Entity\Repository\AccountGroupTaxCodeRepository")
 * @ORM\Table(name="orob2b_tax_acc_group_tax_code")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="orob2b_tax_account_group_tax_code_index",
 *      routeView="orob2b_tax_account_group_tax_code_view",
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
class AccountGroupTaxCode extends AbstractTaxCode
{
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
        $this->accountGroups = new ArrayCollection();
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
}
