<?php

namespace Oro\Bundle\ShippingBundle\Bundle\Tests\Unit\EventListener\Cache;

use Oro\Bundle\ShippingBundle\EventListener\Cache\ShippingMethodsConfigsRuleChangeListener;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;

class ShippingMethodsConfigsRuleChangeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodsConfigsRuleChangeListener
     */
    protected $listener;

    public function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShippingPriceCache $priceCache */
        $priceCache = $this->getMockBuilder(ShippingPriceCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $priceCache
            ->expects(static::once())
            ->method('deleteAllPrices');

        $this->listener = new ShippingMethodsConfigsRuleChangeListener($priceCache);
    }

    public function testPostPersist()
    {
        $this->listener->postPersist();
    }

    public function testPostRemove()
    {
        $this->listener->postRemove();
    }
}
