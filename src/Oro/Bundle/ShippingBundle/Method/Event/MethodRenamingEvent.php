<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MethodRenamingEvent extends Event
{
    const NAME = 'oro_shipping.method_renaming';

    /**
     * @var string
     */
    private $oldMethodId;

    /**
     * @var string
     */
    private $newMethodId;

    /**
     * @param string $oldId
     * @param string $newId
     */
    public function __construct($oldId, $newId)
    {
        $this->oldMethodId = $oldId;
        $this->newMethodId = $newId;
    }

    /**
     * @return int|string
     */
    public function getOldMethodIdentifier()
    {
        return $this->oldMethodId;
    }

    /**
     * @return int|string
     */
    public function getNewMethodIdentifier()
    {
        return $this->newMethodId;
    }
}
