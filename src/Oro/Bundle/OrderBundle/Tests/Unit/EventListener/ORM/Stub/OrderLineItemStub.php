<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class OrderLineItemStub extends OrderLineItem
{
    /** @var EnumOptionInterface */
    protected $internalStatus;

    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }

    /**
     * @return EnumOptionInterface
     */
    public function getInternalStatus()
    {
        return $this->internalStatus;
    }

    /**
     * @param EnumOptionInterface $internalStatus
     *
     * @return $this
     */
    public function setInternalStatus($internalStatus)
    {
        $this->internalStatus = $internalStatus;

        return $this;
    }
}
