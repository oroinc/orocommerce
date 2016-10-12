<?php

namespace Oro\Bundle\OrderBundle\Entity;

/**
 * @ORM\Entity
 * @ORM\Table("oro_order_shipping_tracking")
 * @Config(
 *     mode="hidden",
 * )
 */
class OrderShippingTracking
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="method", type="string", length=255, nullable=true)
     */
    protected $method;

    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", length=255, nullable=true)
     */
    protected $number;

    /**
     * @var Order
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrderBundle\Entity\Order", inversedBy="shippingTrackings")
     * @ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $order;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return OrderShippingTracking
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     *
     * @return OrderShippingTracking
     */
    public function setNumber($number)
    {
        $this->number = $number;
        
        return $this;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param Order $order
     *
     * @return OrderShippingTracking
     */
    public function setOrder($order)
    {
        $this->order = $order;
        
        return $this;
    }
}
