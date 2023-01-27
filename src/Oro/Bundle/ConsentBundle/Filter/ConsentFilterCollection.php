<?php

namespace Oro\Bundle\ConsentBundle\Filter;

/**
 * Represents a read-only collection of consent filters.
 */
class ConsentFilterCollection implements \IteratorAggregate
{
    /** @var iterable<ConsentFilterInterface> */
    private iterable $filters;

    /**
     * @paran iterable<ConsentFilterInterface> $filters
     */
    public function __construct(iterable $filters)
    {
        $this->filters = $filters;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): \Traversable
    {
        return (function () {
            foreach ($this->filters as $filter) {
                yield $filter;
            }
        })();
    }
}
