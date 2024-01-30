<?php

namespace Oro\Component\Checkout\DataProvider;

/**
 * Represents a service to provide info to build collection of line items by the given source entity.
 */
interface CheckoutDataProviderInterface
{
    public function isEntitySupported(object $entity): bool;

    public function getData(object $entity): array;
}
