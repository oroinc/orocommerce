<?php

namespace Oro\Bundle\CustomerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CustomerBundle\Model\ExtendCustomerGroup;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\CustomerBundle\Entity\Repository\CustomerGroupRepository")
 * @ORM\Table(
 *      name="oro_customer_group",
 *      indexes={
 *          @ORM\Index(name="oro_customer_group_name_idx", columns={"name"})
 *      }
 * )
 * @Config(
 *      routeName="oro_customer_customer_group_index",
 *      routeView="oro_customer_customer_group_view",
 *      routeUpdate="oro_customer_customer_group_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-users"
 *          },
 *          "form"={
 *              "form_type"="oro_customer_customer_group_select",
 *              "grid_name"="customer-groups-select-grid",
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
class CustomerGroup extends ExtendCustomerGroup
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $name;

    /**
     * @var Collection|Customer[]
     *
     * @ORM\OneToMany(targetEntity="Oro\Bundle\CustomerBundle\Entity\Customer", mappedBy="group")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     **/
    protected $customers;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->customers = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return CustomerGroup
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add customer
     *
     * @param Customer $customer
     * @return CustomerGroup
     */
    public function addCustomer(Customer $customer)
    {
        if (!$this->customers->contains($customer)) {
            $customer->setGroup($this);
            $this->customers->add($customer);
        }

        return $this;
    }

    /**
     * Remove customer
     *
     * @param Customer $customer
     */
    public function removeCustomer(Customer $customer)
    {
        if ($this->customers->contains($customer)) {
            $this->customers->removeElement($customer);
        }
    }

    /**
     * Get customers
     *
     * @return Collection|Customer[]
     */
    public function getCustomers()
    {
        return $this->customers;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }
}
