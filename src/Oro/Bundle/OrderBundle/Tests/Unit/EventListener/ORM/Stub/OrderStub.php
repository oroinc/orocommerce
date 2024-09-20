<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

class OrderStub extends Order
{
    private ?EnumOptionInterface $internalStatus = null;
    private ?EnumOptionInterface $status = null;

    public function getInternalStatus(): ?EnumOptionInterface
    {
        return $this->internalStatus;
    }

    public function setInternalStatus(?EnumOptionInterface $internalStatus): self
    {
        $this->internalStatus = $internalStatus;

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

    public function unsetWebsite()
    {
        $this->website = null;
    }
}
