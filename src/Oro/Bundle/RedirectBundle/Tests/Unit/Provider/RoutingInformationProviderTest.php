<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Provider;

use Oro\Bundle\RedirectBundle\Exception\UnsupportedEntityException;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProviderInterface;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class RoutingInformationProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param RoutingInformationProviderInterface[] $providers [entity class => provider, ...]
     *
     * @return RoutingInformationProvider
     */
    private function getRoutingInformationProvider(array $providers): RoutingInformationProvider
    {
        $containerBuilder = TestContainerBuilder::create();
        foreach ($providers as $entityClass => $provider) {
            $containerBuilder->add($entityClass, $provider);
        }

        return new RoutingInformationProvider(
            array_keys($providers),
            $containerBuilder->getContainer($this)
        );
    }

    public function testIsSupported()
    {
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $entity = new \stdClass();
        $registry = $this->getRoutingInformationProvider(['stdClass' => $provider]);
        $this->assertTrue($registry->isSupported($entity));
    }

    public function testIsNotSupported()
    {
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupported')
            ->willReturn(false);

        $entity = new \stdClass();
        $registry = $this->getRoutingInformationProvider(['stdClass' => $provider]);
        $this->assertFalse($registry->isSupported($entity));
    }

    public function testIsNoProviders()
    {
        $entity = new \stdClass();
        $registry = $this->getRoutingInformationProvider([]);
        $this->assertFalse($registry->isSupported($entity));
    }

    public function testGetRouteData()
    {
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $routeData = $this->createMock(RouteData::class);
        $provider->expects($this->once())
            ->method('getRouteData')
            ->willReturn($routeData);

        $entity = new \stdClass();
        $registry = $this->getRoutingInformationProvider(['stdClass' => $provider]);
        $this->assertEquals($routeData, $registry->getRouteData($entity));
    }

    public function testGetUrlPrefix()
    {
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupported')
            ->willReturn(true);

        $prefix = 'test';
        $provider->expects($this->once())
            ->method('getUrlPrefix')
            ->willReturn($prefix);

        $entity = new \stdClass();
        $registry = $this->getRoutingInformationProvider(['stdClass' => $provider]);
        $this->assertEquals($prefix, $registry->getUrlPrefix($entity));
    }

    public function testGetEntityClasses()
    {
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $registry = $this->getRoutingInformationProvider(['stdClass' => $provider]);
        $this->assertEquals(['stdClass'], $registry->getEntityClasses());
    }

    public function testUnsupportedEntityException()
    {
        $provider = $this->createMock(RoutingInformationProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupported')
            ->willReturn(false);

        $entity = new \stdClass();
        $registry = $this->getRoutingInformationProvider(['stdClass' => $provider]);

        $this->expectException(UnsupportedEntityException::class);

        $registry->getUrlPrefix($entity);
    }
}
