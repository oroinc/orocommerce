<?php

namespace OroB2B\Bundle\OrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use OroB2B\Bundle\OrderBundle\Model\ExtendOrderAddress;

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
     * @var ArrayCollection|AddressType[]
     *
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\AddressBundle\Entity\AddressType")
     * @ORM\JoinTable(
     *     name="orob2b_order_address_type",
     *     joinColumns={@ORM\JoinColumn(name="order_address_id", referencedColumnName="id", onDelete="CASCADE")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="type_name", referencedColumnName="name")}
     * )
     **/
    protected $types;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Order", inversedBy="addresses",cascade={"persist"})
     * @ORM\JoinColumn(name="owner_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $order;

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     * @return OrderAddress
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }
}
