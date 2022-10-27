<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MethodRemovalEvent extends Event
{
    const NAME = 'oro_shipping.method_removal';

    /**
     * @var int|string
     */
    private $methodId;

    /**
     * @param int|string $id
     */
    public function __construct($id)
    {
        $this->methodId = $id;
    }

    /**
     * @return int|string
     */
    public function getMethodIdentifier()
    {
        return $this->methodId;
    }
}
