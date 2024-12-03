<?php

namespace Oro\Bundle\OrderBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;

/**
 * Represents shipping tracking record for an order.
 */
#[ORM\Entity]
#[ORM\Table('oro_order_shipping_tracking')]
#[Config]
class OrderShippingTracking
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'method', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $method = null;

    #[ORM\Column(name: 'number', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $number = null;

    #[ORM\ManyToMany(targetEntity: Order::class, inversedBy: 'shippingTrackings')]
    #[ORM\JoinTable(name: 'oro_order_shipping_trackings')]
    #[ORM\JoinColumn(name: 'tracking_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'order_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Collection $orders = null;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getOrder(): ?Order
    {
        foreach ($this->orders as $order) {
            if (!$order->getSubOrders()->isEmpty()) {
                return $order;
            }
        }

        if ($this->orders->count()) {
            return $this->orders->first();
        }

        return null;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->contains($order)) {
            $this->orders->removeElement($order);
        }

        return $this;
    }

    public function getOrders(): ?Collection
    {
        return $this->orders;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getMethod();
    }
}
