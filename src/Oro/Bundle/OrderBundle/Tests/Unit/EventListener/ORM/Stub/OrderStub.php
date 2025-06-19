<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderStub extends Order
{
    private ?EnumOptionInterface $internalStatus = null;
    private ?EnumOptionInterface $status = null;
    private ?EnumOptionInterface $shippingStatus = null;
    private array $serializedData = [];

    public function getInternalStatus(): ?EnumOptionInterface
    {
        return $this->internalStatus;
    }

    public function setInternalStatus(?EnumOptionInterface $status): self
    {
        $this->internalStatus = $status;

        return $this;
    }

    public function getStatus(): ?EnumOptionInterface
    {
        return $this->status;
    }

    public function setStatus(?EnumOptionInterface $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getShippingStatus(): ?EnumOptionInterface
    {
        return $this->shippingStatus;
    }

    public function setShippingStatus(?EnumOptionInterface $status): self
    {
        $this->shippingStatus = $status;

        return $this;
    }

    public function unsetWebsite()
    {
        $this->website = null;
    }

    public function getSerializedData(): array
    {
        return $this->serializedData;
    }

    public function setSerializedData(array $serializedData): self
    {
        $this->serializedData = $serializedData;

        return $this;
    }
}
