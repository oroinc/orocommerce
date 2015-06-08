<?php

namespace OroB2B\Bundle\PricingBundle\Event;

use Symfony\Component\EventDispatcher\Event;

abstract class AbstractProductPricesRemoveEvent extends Event
{
    /**
     * @var array
     */
    protected $args = [];

    /**
     * @param array $args
     */
    public function __construct(array $args = [])
    {
        $this->args = $args;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }
}
