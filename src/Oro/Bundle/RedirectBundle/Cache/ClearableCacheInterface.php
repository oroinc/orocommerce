<?php

namespace Oro\Bundle\RedirectBundle\Cache;

/**
 * Interface for cache that can be cleared
 */
interface ClearableCacheInterface
{
    public function deleteAll() : void;
}
