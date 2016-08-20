<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\AccountBundle\Entity\AccountAddress;
use Oro\Bundle\AccountBundle\Entity\AccountUserAddress;
use Oro\Bundle\OrderBundle\Model\ExtendOrderAddress;

/**
 * @ORM\Table("orob2b_order_address")
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
class OrderAddress extends ExtendOrderAddress
{
    /**
     * @var AccountAddress
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\AccountAddress")
     * @ORM\JoinColumn(name="account_address_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $accountAddress;

    /**
     * @var AccountUserAddress
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\AccountUserAddress")
     * @ORM\JoinColumn(name="account_user_address_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $accountUserAddress;

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
     * Set accountAddress
     *
     * @param AccountAddress|null $accountAddress
     *
     * @return OrderAddress
     */
    public function setAccountAddress(AccountAddress $accountAddress = null)
    {
        $this->accountAddress = $accountAddress;

        return $this;
    }

    /**
     * Get accountUserAddress
     *
     * @return AccountAddress|null
     */
    public function getAccountAddress()
    {
        return $this->accountAddress;
    }

    /**
     * Set accountUserAddress
     *
     * @param AccountUserAddress|null $accountUserAddress
     *
     * @return OrderAddress
     */
    public function setAccountUserAddress(AccountUserAddress $accountUserAddress = null)
    {
        $this->accountUserAddress = $accountUserAddress;

        return $this;
    }

    /**
     * Get accountUserAddress
     *
     * @return AccountUserAddress|null
     */
    public function getAccountUserAddress()
    {
        return $this->accountUserAddress;
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
