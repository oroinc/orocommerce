<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="orob2b_customer_group",
 *      indexes={
 *          @ORM\Index(name="orob2b_customer_group_name_idx", columns={"name"})
 *      }
 * )
 *
 * @Config(
 *      routeName="orob2b_customer_group_index",
 *      routeView="orob2b_customer_group_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-group"
 *          },
 *          "form"={
 *              "form_type"="orob2b_customer_group_select",
 *              "grid_name"="customer-groups-select-grid",
 *          }
 *      }
 * )
 */
class CustomerGroup
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
     */
    protected $name;

    /**
     * @var Collection|Customer[]
     *
     * @ORM\OneToMany(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\Customer", mappedBy="group")
     **/
    protected $customers;

    /**
     * @todo Remove this relation
     * @var PriceList
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\PricingBundle\Entity\PriceList", cascade={"persist"})
     * @ORM\JoinColumn(name="price_list_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    protected $priceList;

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
     * @param PriceList $priceList
     *
     * @return CustomerGroup
     */
    public function setPriceList(PriceList $priceList = null)
    {
        $this->priceList = $priceList;

        return $this;
    }

    /**
     * @return PriceList
     */
    public function getPriceList()
    {
        return $this->priceList;
    }
}
