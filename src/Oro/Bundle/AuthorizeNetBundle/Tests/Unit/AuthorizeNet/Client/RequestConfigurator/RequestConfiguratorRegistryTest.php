<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Client\RequestConfigurator;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator\RequestConfiguratorInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator\RequestConfiguratorRegistry;

class RequestConfiguratorRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestConfiguratorRegistry
     */
    protected $requestConfiguratorRegistry;

    protected function setUp()
    {
        $this->requestConfiguratorRegistry = new RequestConfiguratorRegistry();
    }

    public function testAddRequestConfigurator()
    {
        $this->assertEmpty($this->requestConfiguratorRegistry->getRequestConfigurators());

        $requestConfigurator = $this->createRequestConfigurator(0);
        $this->requestConfiguratorRegistry->addRequestConfigurator($requestConfigurator);
        $this->assertCount(1, $this->requestConfiguratorRegistry->getRequestConfigurators());

        $higherRequestConfigurator = $this->createRequestConfigurator(10);
        $this->requestConfiguratorRegistry->addRequestConfigurator($higherRequestConfigurator);

        $configurators = $this->requestConfiguratorRegistry->getRequestConfigurators();
        $this->assertCount(2, $configurators);

        // higher priority - first execution
        $this->assertSame($configurators[0], $higherRequestConfigurator);
        $this->assertSame($configurators[1], $requestConfigurator);
    }

    /**
     * @param int $priority
     * @return RequestConfiguratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createRequestConfigurator($priority = 0)
    {
        $requestConfigurator = $this->createMock(RequestConfiguratorInterface::class);
        $requestConfigurator->expects($this->any())->method('getPriority')->willReturn($priority);

        return $requestConfigurator;
    }
}
