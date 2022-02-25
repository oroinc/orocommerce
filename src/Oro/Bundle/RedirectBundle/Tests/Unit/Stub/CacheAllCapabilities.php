<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Stub;

use Oro\Bundle\RedirectBundle\Cache\ClearableCacheInterface;
use Oro\Bundle\RedirectBundle\Cache\FlushableCacheInterface;

abstract class CacheAllCapabilities implements FlushableCacheInterface, ClearableCacheInterface
{
}
