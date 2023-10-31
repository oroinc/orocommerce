<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\ORM\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class OrderLineItemStub extends OrderLineItem
{
    /** @var AbstractEnumValue */
    protected $internalStatus;

    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }

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
}
