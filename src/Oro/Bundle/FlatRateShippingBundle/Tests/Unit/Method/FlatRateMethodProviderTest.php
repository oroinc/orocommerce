<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FlatRateShippingBundle\Builder\FlatRateMethodFromChannelBuilder;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethod;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethodProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;

class FlatRateMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|FlatRateMethodFromChannelBuilder */
    private $methodBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ChannelRepository */
    private $channelRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    private $doctrineHelper;

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

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->channelRepository = $this->getMockBuilder(ChannelRepository::class)
            ->disableOriginalConstructor()->getMock();

        $this->channelRepository->expects(static::any())
            ->method('findByType')
            ->willReturn([$enabledChannel, $disabledChannel]);

        $this->doctrineHelper
            ->method('getEntityRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($this->channelRepository);

        $this->provider = new FlatRateMethodProvider($this->doctrineHelper, $this->methodBuilder);
    }

    public function testGetShippingMethodsReturnsCorrectObjects()
    {
        $actualMethods = $this->provider->getShippingMethods();

        static::assertCount(1, $actualMethods);
        static::assertSame($this->method, array_shift($actualMethods));
    }

    public function testGetShippingMethodReturnsCorrectObject()
    {
        $actualMethod = $this->provider->getShippingMethod($this->method->getIdentifier());

        static::assertSame($this->method, $actualMethod);
    }

    public function testGetShippingMethodReturnsNull()
    {
        static::assertNull($this->provider->getShippingMethod(''));
    }

    public function testHasShippingMethodOnCorrectIdentifier()
    {
        static::assertTrue($this->provider->hasShippingMethod($this->method->getIdentifier()));
    }

    public function testHasShippingMethodOnWrongIdentifier()
    {
        static::assertFalse($this->provider->hasShippingMethod(''));
    }
}
