<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Stub;

use Oro\Bundle\RedirectBundle\Cache\ClearableCacheInterface;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;
use Oro\Bundle\RedirectBundle\Cache\UrlCacheInterface;

abstract class UrlCacheAllCapabilities implements UrlCacheInterface, FlushableCacheInterface, ClearableCacheInterface
{
}
