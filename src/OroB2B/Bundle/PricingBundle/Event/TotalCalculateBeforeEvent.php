<?php

namespace OroB2B\Bundle\PricingBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class TotalCalculateBeforeEvent extends Event
{
    /** Event can be used for prepare entity from request for dynamic totals calculation */
    const NAME = 'orob2b_pricing.total_calculate_before_event';

    /** @var object */
    protected $entity;

    /** @var Request|null */
    protected $request;

    /**
     * @param $entity
     * @param Request|null $request
     */
    public function __construct($entity, Request $request = null)
    {
        $this->entity = $entity;
        $this->request = $request;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return null|Request
     */
    public function getRequest()
    {
        return$this->request;
    }
}
