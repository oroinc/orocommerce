<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Stub;

use Doctrine\Common\Cache\ClearableCache;
use Doctrine\Common\Cache\FlushableCache;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;

abstract class UrlCacheAllCapabilities implements UrlCacheInterface, FlushableCache, ClearableCache
{
}
