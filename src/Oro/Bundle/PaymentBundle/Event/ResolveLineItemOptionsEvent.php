<?php

namespace Oro\Bundle\PaymentBundle\Event;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\LineItemsAwareInterface;

use Symfony\Component\EventDispatcher\Event;

class ResolveLineItemOptionsEvent extends Event
{
    const NAME = 'oro_payment.event.resolve_line_item_options';

    /** @var LineItemsAwareInterface */
    protected $entity;

    /** @var array */
    protected $keys;

    /** @var array */
    protected $options;

    /**
     * @param LineItemsAwareInterface $entity
     * @param array $keys
     */
    public function __construct(LineItemsAwareInterface $entity, array $keys)
    {
        if (count($keys) != 4) {
            throw new \InvalidArgumentException('4 key values expected');
        }
        $this->entity = $entity;
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
     * @return LineItemsAwareInterface
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
