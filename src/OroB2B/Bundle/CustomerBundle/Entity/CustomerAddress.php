<?php

namespace OroB2B\Bundle\CustomerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;

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
     */
    protected $addressesToTypes;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return entity for many-to-many entity.
     * Should be compatible with AbstractAddressToAddressType
     *
     * @return AbstractAddressToAddressType
     */
    protected function getAddressToAddressTypeEntity()
    {
        return new CustomerAddressToAddressType();
    }
}
