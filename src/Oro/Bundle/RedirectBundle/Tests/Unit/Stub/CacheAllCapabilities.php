<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Stub;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\Cache\MultiGetCache;
use Doctrine\Common\Cache\MultiPutCache;

abstract class CacheAllCapabilities implements Cache, FlushableCache, ClearableCache, MultiPutCache, MultiGetCache
{
}
