<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FlatRateBundle\Builder\FlatRateMethodFromChannelBuilder;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethod;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethodProvider;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Component\Testing\Unit\EntityTrait;

class FlatRateMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FlatRateMethodFromChannelBuilder */
    private $methodBuilder;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ChannelRepository */
    private $channelRepository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var FlatRateMethod */
    private $enabledMethod;

    /** @var FlatRateMethod */
    private $disabledMethod;

    /** @var FlatRateMethodProvider */
    private $provider;

    public function setUp()
    {
        $this->enabledMethod = new FlatRateMethod('test_1', 1, true);
        $this->disabledMethod = new FlatRateMethod('test_2', 2, true);

        $enabledChannel = $this->getEntity(Channel::class, ['id' => 1, 'name' => 'ch_enabled', 'enabled' => true]);
        $disabledChannel = $this->getEntity(Channel::class, ['id' => 2, 'name' => 'ch_disabled', 'enabled' => false]);

        $this->methodBuilder = $this->getMockBuilder(FlatRateMethodFromChannelBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->methodBuilder->expects(static::at(0))
            ->method('build')
            ->with($enabledChannel)
            ->willReturn($this->enabledMethod);
        $this->methodBuilder->expects(static::at(1))
            ->method('build')
            ->with($disabledChannel)
            ->willReturn($this->disabledMethod);

        $this->channelRepository = $this->getMockBuilder(ChannelRepository::class)
            ->disableOriginalConstructor()->getMock();
        $this->channelRepository->expects(static::any())
            ->method('findByType')
            ->willReturn([$enabledChannel, $disabledChannel]);

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper
            ->method('getEntityRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($this->channelRepository);

        $this->provider = new FlatRateMethodProvider($this->doctrineHelper, $this->methodBuilder);
    }

    public function testGetShippingMethodsReturnsCorrectObjects()
    {
        $actualMethods = $this->provider->getShippingMethods();

        static::assertCount(2, $actualMethods);
        static::assertSame($this->enabledMethod, array_shift($actualMethods));
    }

    public function testGetShippingMethodReturnsCorrectObject()
    {
        $actualMethod = $this->provider->getShippingMethod($this->enabledMethod->getIdentifier());

        static::assertSame($this->enabledMethod, $actualMethod);
    }

    public function testGetShippingMethodReturnsNull()
    {
        static::assertNull($this->provider->getShippingMethod(''));
    }

    public function testHasShippingMethodOnCorrectIdentifier()
    {
        static::assertTrue($this->provider->hasShippingMethod($this->enabledMethod->getIdentifier()));
    }

    public function testHasShippingMethodOnWrongIdentifier()
    {
        static::assertFalse($this->provider->hasShippingMethod(''));
    }
}
