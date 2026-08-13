<?php

namespace Oro\Bundle\TaxBundle\Event;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Dispatches tax resolution events during the tax calculation process.
 *
 * This dispatcher orchestrates the tax calculation workflow by dispatching {@see ResolveTaxEvent}
 * events in three phases: RESOLVE_BEFORE, RESOLVE, and RESOLVE_AFTER. Tax resolvers listen to these events
 * to calculate taxes, apply tax rules, and perform post-calculation adjustments.
 * It ensures that all registered resolvers have an opportunity to contribute to the final tax calculation result.
 * It also dispatches {@see LoadTaxBeforeEvent} before the tax of an object is loaded.
 */
class TaxEventDispatcher
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

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

        $this->eventDispatcher->dispatch($event, ResolveTaxEvent::RESOLVE_BEFORE);
        $this->eventDispatcher->dispatch($event, ResolveTaxEvent::RESOLVE);
        $this->eventDispatcher->dispatch($event, ResolveTaxEvent::RESOLVE_AFTER);

        return $taxable->getResult();
    }

    /**
     * Gives listeners a chance to preload the data required to load the tax of the given object
     * and of its nested objects, e.g. tax values of order line items.
     */
    public function dispatchLoadTaxBefore(object $object): void
    {
        $this->eventDispatcher->dispatch(new LoadTaxBeforeEvent($object));
    }
}
