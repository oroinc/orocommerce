<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Oro\Bundle\RedirectBundle\Exception\UnsupportedEntityException;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;

class RoutingInformationProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testIsSupported()
    {
        /** @var RoutingInformationProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $entity = new \stdClass();
        $registry = new RoutingInformationProvider();
        $registry->registerProvider($provider, 'stdClass');
        $this->assertTrue($registry->isSupported($entity));
    }

    public function testIsNotSupported()
    {
        /** @var RoutingInformationProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupported')
            ->willReturn(false);

        $entity = new \stdClass();
        $registry = new RoutingInformationProvider();
        $registry->registerProvider($provider, 'stdClass');
        $this->assertFalse($registry->isSupported($entity));
    }

    public function testIsNoProviders()
    {
        $entity = new \stdClass();
        $registry = new RoutingInformationProvider();
        $this->assertFalse($registry->isSupported($entity));
    }

    public function testGetRouteData()
    {
        /** @var RoutingInformationProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        /** @var RouteData|\PHPUnit_Framework_MockObject_MockObject $routeData */
        $routeData = $this->getMockBuilder(RouteData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $provider->expects($this->once())
            ->method('getRouteData')
            ->willReturn($routeData);

        $entity = new \stdClass();
        $registry = new RoutingInformationProvider();
        $registry->registerProvider($provider, 'stdClass');
        $this->assertEquals($routeData, $registry->getRouteData($entity));
    }

    public function testGetUrlPrefix()
    {
        /** @var RoutingInformationProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $prefix = 'test';
        $provider->expects($this->once())
            ->method('getUrlPrefix')
            ->willReturn($prefix);

        $entity = new \stdClass();
        $registry = new RoutingInformationProvider();
        $registry->registerProvider($provider, 'stdClass');
        $this->assertEquals($prefix, $registry->getUrlPrefix($entity));
    }

    public function testGetEntityClasses()
    {
        /** @var RoutingInformationProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $registry = new RoutingInformationProvider();
        $registry->registerProvider($provider, 'stdClass');
        $this->assertEquals(['stdClass'], $registry->getEntityClasses());
    }

    public function testUnsupportedEntityException()
    {
        /** @var RoutingInformationProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupported')
            ->willReturn(false);

        $entity = new \stdClass();
        $registry = new RoutingInformationProvider();
        $registry->registerProvider($provider, 'stdClass');

        $this->expectException(UnsupportedEntityException::class);

        $registry->getUrlPrefix($entity);
    }
}
