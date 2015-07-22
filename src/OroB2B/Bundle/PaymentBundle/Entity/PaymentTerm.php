<?php

namespace OroB2B\Bundle\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup;

/**
 * @ORM\Table(name="orob2b_payment_term")
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PaymentBundle\Entity\Repository\PaymentTermRepository")
 * @Config(
 *      routeName="orob2b_payment_term_index",
 *      routeView="orob2b_payment_term_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-usd"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          },
 *          "form"={
 *              "form_type"="orob2b_payment_term_select",
 *              "grid_name"="payment-terms-select-grid",
 *          }
 *      }
 * )
 */
class PaymentTerm
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="label", type="string")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $label;

    /**
     * @var CustomerGroup[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\CustomerGroup")
     * @ORM\JoinTable(
     *      name="orob2b_payment_term_to_c_group",
     *      joinColumns={
     *          @ORM\JoinColumn(name="payment_term_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="customer_group_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $customerGroups;

    /**
     * @var Customer[]|Collection
     *
     * @ORM\ManyToMany(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\Customer")
     * @ORM\JoinTable(
     *      name="orob2b_payment_t_to_customer",
     *      joinColumns={
     *          @ORM\JoinColumn(name="payment_term_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="customer_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $customers;

    public function __construct()
    {
        $this->customerGroups = new ArrayCollection();
        $this->customers = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set label
     *
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param CustomerGroup $customerGroup
     *
     * @return PaymentTerm
     */
    public function addCustomerGroup(CustomerGroup $customerGroup)
    {
        if (!$this->customerGroups->contains($customerGroup)) {
            $this->customerGroups->add($customerGroup);
        }

        return $this;
    }

    /**
     * @param CustomerGroup $customerGroup
     *
     * @return PaymentTerm
     */
    public function removeCustomerGroup(CustomerGroup $customerGroup)
    {
        if ($this->customerGroups->contains($customerGroup)) {
            $this->customerGroups->removeElement($customerGroup);
        }

        return $this;
    }

    /**
     * Get customer groups
     *
     * @return Collection|CustomerGroup[]
     */
    public function getCustomerGroups()
    {
        return $this->customerGroups;
    }

    /**
     * @param Customer $customer
     *
     * @return PaymentTerm
     */
    public function addCustomer(Customer $customer)
    {
        if (!$this->customers->contains($customer)) {
            $this->customers->add($customer);
        }

        return $this;
    }

    /**
     * @param Customer $customer
     *
     * @return PaymentTerm
     */
    public function removeCustomer(Customer $customer)
    {
        if ($this->customers->contains($customer)) {
            $this->customers->removeElement($customer);
        }

        return $this;
    }

    /**
     * Get customer groups
     *
     * @return Collection|Customer[]
     */
    public function getCustomers()
    {
        return $this->customers;
    }
}
