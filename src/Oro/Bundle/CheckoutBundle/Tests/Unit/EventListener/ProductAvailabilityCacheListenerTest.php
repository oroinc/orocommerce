<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CheckoutBundle\EventListener\ProductAvailabilityCacheListener;

class ProductAvailabilityCacheListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * @var ProductAvailabilityCacheListener
     */
    private $productAvailabilityCacheListener;

    protected function setUp()
    {
        $this->cache = $this->createMock(CacheProvider::class);

        $this->productAvailabilityCacheListener = new ProductAvailabilityCacheListener($this->cache);
    }

    public function testPostFlush()
    {
        $this->cache->expects(static::once())
            ->method('deleteAll');

        $this->productAvailabilityCacheListener->postFlush();
    }
}
