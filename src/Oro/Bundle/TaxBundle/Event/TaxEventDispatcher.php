<?php

namespace Oro\Bundle\TaxBundle\Event;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;

class TaxEventDispatcher
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Taxable $taxable
     * @return Result
     */
    public function dispatch(Taxable $taxable)
    {
        $event = new ResolveTaxEvent($taxable);

        $this->eventDispatcher->dispatch(ResolveTaxEvent::RESOLVE_BEFORE, $event);
        $this->eventDispatcher->dispatch(ResolveTaxEvent::RESOLVE, $event);
        $this->eventDispatcher->dispatch(ResolveTaxEvent::RESOLVE_AFTER, $event);

        return $taxable->getResult();
    }
}
