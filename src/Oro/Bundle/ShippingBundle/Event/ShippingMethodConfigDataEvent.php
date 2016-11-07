<?php

namespace Oro\Bundle\ShippingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ShippingMethodConfigDataEvent extends Event
{
    const NAME = 'oro_shipping_method.config_data';

    /**
     * @var int|string
     */
    protected $methodIdentifier;

    /**
     * @var string
     */
    protected $template;

    /**
     * @param int|string $identifier
     */
    public function __construct($identifier)
    {
        $this->methodIdentifier = $identifier;
    }

    /**
     * @return int|string
     */
    public function getMethodIdentifier()
    {
        return $this->methodIdentifier;
    }

    /**
     * Returns shipping method config template
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }
}
