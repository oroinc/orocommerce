<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\Method;

use Oro\Bundle\FlatRateBundle\Builder\FlatRateMethodFromChannelBuilder;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethod;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethodProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;

class FlatRateMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|FlatRateMethodFromChannelBuilder */
    private $methodBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ChannelRepository */
    private $channelRepository;

    /** @var FlatRateMethod */
    private $method;

    /** @var FlatRateMethodProvider */
    private $provider;

    public function setUp()
    {
        $this->method = new FlatRateMethod('test', 1);

        $this->methodBuilder = $this->getMockBuilder(FlatRateMethodFromChannelBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->methodBuilder->expects(static::any())
            ->method('build')
            ->willReturn($this->method);

        $enabledChannel = new Channel();

        $disabledChannel = new Channel();
        $disabledChannel->setEnabled(false);

        $this->channelRepository = $this->getMockBuilder(ChannelRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->channelRepository->expects(static::any())
            ->method('findByType')
            ->willReturn([$enabledChannel, $disabledChannel]);

        $this->provider = new FlatRateMethodProvider($this->methodBuilder);
    }

    public function testGetShippingMethodsReturnsCorrectObjects()
    {
        $this->provider->setChannelRepository($this->channelRepository);

        $actualMethods = $this->provider->getShippingMethods();

        static::assertCount(1, $actualMethods);
        static::assertSame($this->method, array_shift($actualMethods));
    }

    public function testGetShippingMethodsWithoutChannelRepositoryReturnsEmptyArray()
    {
        $actualMethods = $this->provider->getShippingMethods();

        static::assertCount(0, $actualMethods);
    }

    public function testGetShippingMethodReturnsCorrectObject()
    {
        $this->provider->setChannelRepository($this->channelRepository);

        $actualMethod = $this->provider->getShippingMethod($this->method->getIdentifier());

        static::assertSame($this->method, $actualMethod);
    }

    public function testGetShippingMethodReturnsNull()
    {
        $this->provider->setChannelRepository($this->channelRepository);

        static::assertNull($this->provider->getShippingMethod(''));
    }

    public function testHasShippingMethodOnCorrectIdentifier()
    {
        $this->provider->setChannelRepository($this->channelRepository);

        static::assertTrue($this->provider->hasShippingMethod($this->method->getIdentifier()));
    }

    public function testHasShippingMethodOnWrongIdentifier()
    {
        $this->provider->setChannelRepository($this->channelRepository);

        static::assertFalse($this->provider->hasShippingMethod(''));
    }
}
