<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderStub extends Order
{
    private ?AbstractEnumValue $internalStatus = null;
    private ?AbstractEnumValue $status = null;
    private array $serializedData = [];

    public function getInternalStatus(): ?AbstractEnumValue
    {
        return $this->internalStatus;
    }

    public function setInternalStatus(?AbstractEnumValue $internalStatus): self
    {
        $this->internalStatus = $internalStatus;

        return $this;
    }

    public function getStatus(): ?AbstractEnumValue
    {
        return $this->status;
    }

    public function setStatus(?AbstractEnumValue $status): self
    {
        $this->status = $status;

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
