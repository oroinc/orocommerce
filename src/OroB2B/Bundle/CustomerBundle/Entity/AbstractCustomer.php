<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="orob2b_customer")
 */
abstract class AbstractCustomer
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
     * @var AbstractCustomer
     */
    protected $parent;

    /**
     * @var ArrayCollection|AbstractCustomer[]
     */
    protected $children;

    /**
     * @var AbstractCustomerGroup
     */
    protected $group;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
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
     * @return $this
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
     * Set parent
     *
     * @param AbstractCustomer $parent
     * @return $this
     */
    public function setParent(AbstractCustomer $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return AbstractCustomer
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set group
     *
     * @param AbstractCustomerGroup $group
     * @return $this
     */
    public function setGroup(AbstractCustomerGroup $group)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group
     *
     * @return AbstractCustomerGroup
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Add child
     *
     * @param AbstractCustomer $child
     * @return $this
     */
    public function addChild(AbstractCustomer $child)
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
        }

        return $this;
    }

    /**
     * Remove child
     *
     * @param AbstractCustomer $child
     */
    public function removeChild(AbstractCustomer $child)
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
        }
    }

    /**
     * Get children
     *
     * @return ArrayCollection|AbstractCustomer[]
     */
    public function getChildren()
    {
        return $this->children;
    }
}
