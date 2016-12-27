<?php
/**
 * Date: 12/27/16
 *
 * @author Portey Vasil <portey@gmail.com>
 */

namespace Oro\Bundle\ShippingBundle\Bundle\Tests\Unit\EventListener\Cache;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\EventListener\Cache\ShippingMethodTypeConfigChangeListener;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;

class ShippingMethodTypeConfigChangeListenerTest extends \PHPUnit_Framework_TestCase
{

    public function testPostUpdateMethod()
    {
        $priceCache = $this->getMockBuilder(ShippingPriceCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $priceCache
            ->expects($this->once())
            ->method('deleteAllPrices');

        $methodConfig = $this->getMockBuilder(ShippingMethodConfig::class)
            ->getMock();

        $methodConfig
            ->expects($this->once())
            ->method('getMethod')
            ->willReturn(FlatRateShippingMethod::IDENTIFIER);

        $typeConfig = $this->getMockBuilder(ShippingMethodTypeConfig::class)
            ->getMock();

        $typeConfig
            ->expects($this->once())
            ->method('getMethodConfig')
            ->willReturn($methodConfig);

        $args = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $args
            ->expects($this->exactly(2))
            ->method('getEntity')
            ->willReturn($typeConfig);

        $listener = new ShippingMethodTypeConfigChangeListener($priceCache);
        $listener->postUpdate($args);
    }

}
