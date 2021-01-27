<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MethodTypeRemovalEvent extends Event
{
    const NAME = 'oro_shipping.method_type_removal';

    /**
     * @var int|string
     */
    private $methodId;

    /**
     * @var int|string
     */
    private $typeId;

    /**
     * @param int|string $methodId
     * @param int|string $typeId
     */
    public function __construct($methodId, $typeId)
    {
        $this->methodId = $methodId;
        $this->typeId = $typeId;
    }

    /**
     * @return int|string
     */
    public function getMethodIdentifier()
    {
        return $this->methodId;
    }

    /**
     * @return int|string
     */
    public function getTypeIdentifier()
    {
        return $this->typeId;
    }
}
