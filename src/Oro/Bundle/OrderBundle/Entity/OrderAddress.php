<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\AddressPhoneAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\OrderBundle\Model\ExtendOrderAddress;

/**
 * Represents billing and shipping address for an order.
 * @ORM\Table("oro_order_address")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *       defaultValues={
 *          "entity"={
 *              "icon"="fa-map-marker"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 * @ORM\Entity
 */
class OrderAddress extends ExtendOrderAddress implements AddressPhoneAwareInterface
{
    /**
     * @var CustomerAddress
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerAddress")
     * @ORM\JoinColumn(name="customer_address_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $customerAddress;

    /**
     * @var CustomerUserAddress
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress")
     * @ORM\JoinColumn(name="customer_user_address_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $customerUserAddress;

    /**
     * @var bool
     *
     * @ORM\Column(name="from_external_source", type="boolean", options={"default"=false})
     */
    protected $fromExternalSource = false;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     * @ConfigField(
     *  defaultValues={
     *      "entity"={
     *          "contact_information"="phone"
     *      }
     *  }
     * )
     */
    protected $phone;

    /**
     * Set customerAddress
     *
     * @param CustomerAddress|null $customerAddress
     *
     * @return OrderAddress
     */
    public function setCustomerAddress(CustomerAddress $customerAddress = null)
    {
        $this->customerAddress = $customerAddress;

        return $this;
    }

    /**
     * Get customerUserAddress
     *
     * @return CustomerAddress|null
     */
    public function getCustomerAddress()
    {
        return $this->customerAddress;
    }

    /**
     * Set customerUserAddress
     *
     * @param CustomerUserAddress|null $customerUserAddress
     *
     * @return OrderAddress
     */
    public function setCustomerUserAddress(CustomerUserAddress $customerUserAddress = null)
    {
        $this->customerUserAddress = $customerUserAddress;

        return $this;
    }

    /**
     * Get customerUserAddress
     *
     * @return CustomerUserAddress|null
     */
    public function getCustomerUserAddress()
    {
        return $this->customerUserAddress;
    }

    /**
     * @return boolean
     */
    public function isFromExternalSource()
    {
        return $this->fromExternalSource;
    }

    /**
     * @param boolean $fromExternalSource
     * @return $this
     */
    public function setFromExternalSource($fromExternalSource)
    {
        $this->fromExternalSource = (bool)$fromExternalSource;

        return $this;
    }

    /**
     * Set phone number
     *
     * @param string $phone
     *
     * @return OrderAddress
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone number
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }
}
