<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\CustomerBundle\Model\ExtendCustomerAddress;

/**
 * @ORM\Table("orob2b_customer_address")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *       defaultValues={
 *          "entity"={
 *              "icon"="icon-map-marker"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 * @ORM\Entity
 */
class CustomerAddress extends ExtendCustomerAddress
{
    /**
     * @ORM\ManyToOne(targetEntity="Customer", inversedBy="addresses", cascade={"persist"})
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $owner;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(
     *      targetEntity="CustomerAddressToAddressType",
     *      mappedBy="address",
     *      cascade={"persist", "remove"},
     *      orphanRemoval=true
     * )
     **/
    protected $addressesToTypes;

    public function __construct()
    {
        $this->addressesToTypes = new ArrayCollection();
        parent::__construct();
    }

    /**
     * Get address types
     *
     * @return Collection|AddressType[]
     */
    public function getTypes()
    {
        $types = new ArrayCollection();
        /** @var CustomerAddressToAddressType $addressToType */
        foreach ($this->getAddressesToTypes() as $addressToType) {
            $types->add($addressToType->getType());
        }

        return $types;
    }

    /**
     * Set address types
     *
     * @param Collection $types
     * @return CustomerAddress
     */
    public function setTypes(Collection $types)
    {
        $this->getAddressesToTypes()->clear();

        /** @var AddressType $type */
        foreach ($types as $type) {
            $this->addType($type);
        }

        return $this;
    }

    /**
     * Remove address type
     *
     * @param AddressType $type
     * @return CustomerAddress
     */
    public function removeType(AddressType $type)
    {
        /** @var CustomerAddressToAddressType $addressesToType */
        foreach ($this->getAddressesToTypes() as $addressesToType) {
            if ($addressesToType->getType()->getName() === $type->getName()) {
                $this->removeAddressesToType($addressesToType);
            }
        }

        return $this;
    }

    /**
     * Add address type
     *
     * @param AddressType $type
     * @return CustomerAddress
     */
    public function addType(AddressType $type)
    {
        $addressToType = new CustomerAddressToAddressType();
        $addressToType->setType($type);
        $addressToType->setAddress($this);
        $this->addAddressesToType($addressToType);

        return $this;
    }

    /**
     * Get default types
     *
     * @return Collection|AddressType[]
     */
    public function getDefaults()
    {
        $defaultTypes = new ArrayCollection();
        /** @var CustomerAddressToAddressType $addressToType */
        foreach ($this->getAddressesToTypes() as $addressToType) {
            if ($addressToType->isDefault()) {
                $defaultTypes->add($addressToType->getType());
            }
        }

        return $defaultTypes;
    }

    /**
     * Set default types
     *
     * @param Collection|AddressType[] $defaults
     * @return CustomerAddress
     */
    public function setDefaults($defaults)
    {
        /** @var CustomerAddressToAddressType $addressToType */
        foreach ($this->getAddressesToTypes() as $addressToType) {
            $addressToType->setDefault(false);
            /** @var AddressType $default */
            foreach ($defaults as $default) {
                if ($addressToType->getType()->getName() === $default->getName()) {
                    $addressToType->setDefault(true);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Add addressesToTypes
     *
     * @param CustomerAddressToAddressType $addressesToTypes
     * @return CustomerAddress
     */
    public function addAddressesToType(CustomerAddressToAddressType $addressesToTypes)
    {
        if (!$this->getAddressesToTypes()->contains($addressesToTypes)) {
            $this->addressesToTypes[] = $addressesToTypes;
        }

        return $this;
    }

    /**
     * Remove addressesToTypes
     *
     * @param CustomerAddressToAddressType $addressesToTypes
     */
    public function removeAddressesToType(CustomerAddressToAddressType $addressesToTypes)
    {
        $this->addressesToTypes->removeElement($addressesToTypes);
    }

    /**
     * Get addressesToTypes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAddressesToTypes()
    {
        return $this->addressesToTypes;
    }

    /**
     * Set customer as owner.
     *
     * @param Customer $owner
     * @return $this
     */
    public function setOwner(Customer $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner customer.
     *
     * @return Customer
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Get address created date/time
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get address last update date/time
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
