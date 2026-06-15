<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
 * Represents shipping tracking record for an order.
 */
#[ORM\Entity]
#[ORM\Table('oro_order_shipping_tracking')]
#[Config(defaultValues: [
    'email' => ['available_in_template' => true],
])]
class OrderShippingTracking
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?int $id = null;

    #[ORM\Column(name: 'method', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?string $method = null;

    #[ORM\Column(name: 'number', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?string $number = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'shippingTrackings')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['email' => ['available_in_template' => true]])]
    protected ?Order $order = null;

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

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getMethod();
    }
}
