<?php

namespace Oro\Bundle\SaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Quote address entity
 * @ORM\Table("oro_quote_address")
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *       defaultValues={
 *          "entity"={
 *              "icon"="fa-map-marker"
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
class QuoteAddress extends AbstractAddress implements ExtendEntityInterface
{
    use ExtendEntityTrait;

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
     * @return QuoteAddress
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
     * @return QuoteAddress
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
     * Set phone number
     *
     * @param string $phone
     *
     * @return QuoteAddress
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
