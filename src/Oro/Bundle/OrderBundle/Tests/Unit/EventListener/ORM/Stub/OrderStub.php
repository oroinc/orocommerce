<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderStub extends Order
{
    /** @var AbstractEnumValue */
    protected $internalStatus;

    /** @var array */
    protected $serializedData = [];

    /**
     * @return AbstractEnumValue
     */
    public function getInternalStatus()
    {
        return $this->internalStatus;
    }

    /**
     * @param AbstractEnumValue $internalStatus
     *
     * @return $this
     */
    public function setInternalStatus($internalStatus)
    {
        $this->internalStatus = $internalStatus;

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
