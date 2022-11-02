<?php

namespace Oro\Bundle\PricingBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractProductPricesRemoveEvent extends Event
{
    /**
     * @var array
     */
    protected $args = [];

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
