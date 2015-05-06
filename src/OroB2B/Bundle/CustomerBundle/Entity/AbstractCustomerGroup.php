<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="orob2b_customer_group",
 *      indexes={
 *          @ORM\Index(name="orob2b_customer_group_name_idx", columns={"name"})
 *      }
 * )
 */
abstract class AbstractCustomerGroup
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
     * @var Collection|AbstractCustomer[]
     */
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
     * @return AbstractCustomerGroup
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
     * @param AbstractCustomer $customer
     * @return AbstractCustomerGroup
     */
    public function addCustomer(AbstractCustomer $customer)
    {
        if (!$this->customers->contains($customer)) {
            $this->customers->add($customer);
        }

        return $this;
    }

    /**
     * Remove customer
     *
     * @param AbstractCustomer $customer
     */
    public function removeCustomer(AbstractCustomer $customer)
    {
        if ($this->customers->contains($customer)) {
            $this->customers->removeElement($customer);
        }
    }

    /**
     * Get customers
     *
     * @return Collection|AbstractCustomer[]
     */
    public function getCustomers()
    {
        return $this->customers;
    }
}
