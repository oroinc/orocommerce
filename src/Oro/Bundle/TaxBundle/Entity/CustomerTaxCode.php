<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\UserBundle\Entity\Ownership\UserAwareTrait;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\TaxBundle\Entity\Repository\CustomerTaxCodeRepository")
 * @ORM\Table(name="oro_tax_customer_tax_code")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="oro_tax_customer_tax_code_index",
 *      routeView="oro_tax_customer_tax_code_view",
 *      routeUpdate="oro_tax_customer_tax_code_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-list-alt"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id",
 *              "organization_field_name"="organization",
 *              "organization_column_name"="organization_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"="commerce"
 *          }
 *      }
 * )
 */
class CustomerTaxCode extends AbstractTaxCode implements OrganizationAwareInterface
{
    use UserAwareTrait;

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
    protected $customers;

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
    protected $customerGroups;

    public function __construct()
    {
        $this->customers = new ArrayCollection();
        $this->customerGroups = new ArrayCollection();
    }

    /**
     * Add customer
     *
     * @param Customer $customer
     * @return $this
     */
    public function addCustomer(Customer $customer)
    {
        if (!$this->customers->contains($customer)) {
            $this->customers->add($customer);
        }

        return $this;
    }

    /**
     * Remove customer
     *
     * @param Customer $customer
     * @return $this
     */
    public function removeCustomer(Customer $customer)
    {
        if ($this->customers->contains($customer)) {
            $this->customers->removeElement($customer);
        }

        return $this;
    }

    /**
     * Get customers
     *
     * @return Customer[]|Collection
     */
    public function getCustomers()
    {
        return $this->customers;
    }

    /**
     * Add customerGroup
     *
     * @param CustomerGroup $customerGroup
     * @return $this
     */
    public function addCustomerGroup(CustomerGroup $customerGroup)
    {
        if (!$this->customerGroups->contains($customerGroup)) {
            $this->customerGroups->add($customerGroup);
        }

        return $this;
    }

    /**
     * Remove customerGroup
     *
     * @param CustomerGroup $customerGroup
     * @return $this
     */
    public function removeCustomerGroup(CustomerGroup $customerGroup)
    {
        if ($this->customerGroups->contains($customerGroup)) {
            $this->customerGroups->removeElement($customerGroup);
        }

        return $this;
    }

    /**
     * Get customerGroups
     *
     * @return CustomerGroup[]|Collection
     */
    public function getCustomerGroups()
    {
        return $this->customerGroups;
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return TaxCodeInterface::TYPE_ACCOUNT;
    }
}
