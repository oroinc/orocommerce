<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Builder;

use Oro\Bundle\FlatRateShippingBundle\Builder\FlatRateMethodFromChannelBuilder;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

class FlatRateMethodFromChannelBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IntegrationShippingMethodFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $factory;

    /**
     * @var FlatRateMethodFromChannelBuilder
     */
    private $builder;

    protected function setUp()
    {
        $this->factory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);

        $this->builder = new FlatRateMethodFromChannelBuilder($this->factory);
    }

    public function testBuildReturnsCorrectObjectWithLabel()
    {
        $expectedMethod = $this->createMock(ShippingMethodInterface::class);

        $channel = $this->getChannelMock();

        $this->factory->expects($this->once())
            ->method('create')
            ->with($channel)
            ->willReturn($expectedMethod);

        static::assertSame($expectedMethod, $this->builder->build($channel));
    }

    /**
     * @return Channel|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getChannelMock()
    {
        return $this->createMock(Channel::class);
    }
}
