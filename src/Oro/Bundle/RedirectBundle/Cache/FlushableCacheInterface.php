<?php

namespace Oro\Bundle\RedirectBundle\Cache;

/**
 * Interface for cache that can be flushed
 */
interface FlushableCacheInterface
{
    public function flushAll() : void;
}
