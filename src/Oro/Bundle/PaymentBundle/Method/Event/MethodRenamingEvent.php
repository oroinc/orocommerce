<?php

namespace Oro\Bundle\PaymentBundle\Method\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MethodRenamingEvent extends Event
{
    const NAME = 'oro_payment.method_renaming';

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
     * @return string
     */
    public function getOldMethodIdentifier()
    {
        return $this->oldMethodId;
    }

    /**
     * @return string
     */
    public function getNewMethodIdentifier()
    {
        return $this->newMethodId;
    }
}
