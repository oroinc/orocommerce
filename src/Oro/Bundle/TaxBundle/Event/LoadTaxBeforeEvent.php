<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before tax is loaded for an object.
 *
 * Listeners may use it to preload the data required to load the tax of the given object and of its nested objects,
 * for example to warm up tax values of order line items with a single query.
 */
class LoadTaxBeforeEvent extends Event
{
    public function __construct(private readonly object $object)
    {
    }

    public function getObject(): object
    {
        return $this->object;
    }
}
