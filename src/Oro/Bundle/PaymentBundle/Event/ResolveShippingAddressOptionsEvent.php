<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ResolveShippingAddressOptionsEvent extends Event
{
    const NAME = 'oro_payment.event.resolve_shipping_address_options';

    /** @var object */
    protected $entity;

    /** @var array */
    protected $keys;

    /** @var array */
    protected $options;

    /**
     * @param object $addressEntity
     * @param array $keys
     */
    public function __construct($addressEntity, array $keys)
    {
        if (count($keys) != 8) {
            throw new \InvalidArgumentException('8 key values expected');
        }
        $this->entity = $addressEntity;
        $this->keys = $keys;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }
}
