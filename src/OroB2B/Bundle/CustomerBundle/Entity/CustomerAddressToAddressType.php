<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AddressBundle\Entity\AddressType;

/**
 * @ORM\Table("orob2b_customer_adr_adr_type",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orob2b_customer_adr_id_type_name_idx", columns={
 *              "customer_address_id",
 *              "type_name"
 *          })
 *      }
 * )
 * @ORM\Entity
 */
class CustomerAddressToAddressType
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
     * @var CustomerAddress
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress", inversedBy="addressesToTypes")
     * @ORM\JoinColumn(name="customer_address_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $address;

    /**
     * @var AddressType
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AddressBundle\Entity\AddressType", cascade={"persist"})
     * @ORM\JoinColumn(name="type_name", referencedColumnName="name", onDelete="CASCADE")
     **/
    protected $type;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=true)
     */
    protected $default;

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
     * Set address
     *
     * @param CustomerAddress $address
     * @return CustomerAddressToAddressType
     */
    public function setAddress(CustomerAddress $address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return CustomerAddress
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set type
     *
     * @param AddressType $type
     * @return CustomerAddressToAddressType
     */
    public function setType(AddressType $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return AddressType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set default
     *
     * @param boolean $default
     * @return CustomerAddressToAddressType
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Get default
     *
     * @return boolean
     */
    public function isDefault()
    {
        return $this->default;
    }
}
