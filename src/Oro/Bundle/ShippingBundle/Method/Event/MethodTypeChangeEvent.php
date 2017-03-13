<?php

namespace Oro\Bundle\ShippingBundle\Method\Event;

use Symfony\Component\EventDispatcher\Event;

class MethodTypeChangeEvent extends Event
{
    const NAME = 'oro_shipping_method_type.change';

    /**
     * @var string[]
     */
    private $availableTypes;

    /**
     * @var string
     */
    private $methodIdentifier;

    /**
     * @var string[]
     */
    private $errorTypes;

    /**
     * @param string[] $availableTypes
     * @param string   $methodIdentifier
     */
    public function __construct(array $availableTypes, $methodIdentifier)
    {
        $this->availableTypes = $availableTypes;
        $this->methodIdentifier = $methodIdentifier;
    }

    /**
     * @return array
     */
    public function getAvailableTypes()
    {
        return $this->availableTypes;
    }

    /**
     * @return string
     */
    public function getMethodIdentifier()
    {
        return $this->methodIdentifier;
    }

    /**
     * @param string $type
     *
     * @return MethodTypeChangeEvent
     */
    public function addErrorType($type)
    {
        $this->errorTypes[] = $type;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getErrorTypes()
    {
        return $this->errorTypes;
    }

    /**
     * @return string
     */
    public function getErrorMessagePlaceholder()
    {
        return 'oro.shipping.method_type.change.error';
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errorTypes);
    }
}
