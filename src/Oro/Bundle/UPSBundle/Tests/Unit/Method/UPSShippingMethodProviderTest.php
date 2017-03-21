<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodProvider;
use Oro\Bundle\UPSBundle\Provider\ChannelType;
use Oro\Component\Testing\Unit\EntityTrait;

class UPSShippingMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UPSShippingMethodProvider
     */
    private $provider;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var IntegrationShippingMethodFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodFactory;

    /**
     * @var UPSShippingMethod
     */
    private $enabledMethod;

    /**
     * @var UPSShippingMethod
     */
    private $disabledMethod;

    public function setUp()
    {
        $repository = $this->createMock(ChannelRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($repository);

        $enabledChannel = $this->getEntity(Channel::class, ['id' => 10, 'name' => 'ch_enabled', 'enabled' => true]);
        $disabledChannel = $this->getEntity(Channel::class, ['id' => 20, 'name' => 'ch_enabled', 'enabled' => false]);

        $repository->expects(static::once())
            ->method('findByType')
            ->with(ChannelType::TYPE)
            ->willReturn([$enabledChannel, $disabledChannel]);

        $this->enabledMethod = $this->createMock(UPSShippingMethod::class);
        $this->enabledMethod
            ->method('getIdentifier')
            ->willReturn('ups_10');

        $this->disabledMethod = $this->createMock(UPSShippingMethod::class);
        $this->disabledMethod
            ->method('getIdentifier')
            ->willReturn('ups_20');

        $this->methodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);
        $this->methodFactory->expects(static::at(0))
            ->method('create')
            ->with($enabledChannel)
            ->willReturn($this->enabledMethod);
        $this->methodFactory->expects(static::at(1))
            ->method('create')
            ->with($disabledChannel)
            ->willReturn($this->disabledMethod);

        $this->provider = new UPSShippingMethodProvider($this->doctrineHelper, $this->methodFactory);
    }

    public function testGetShippingMethods()
    {
        $methods = $this->provider->getShippingMethods();
        static::assertCount(2, $methods);
        $actualMethod = reset($methods);
        static::assertSame($this->enabledMethod, $actualMethod);
    }

    public function testGetShippingMethod()
    {
        $method = $this->provider->getShippingMethod($this->enabledMethod->getIdentifier());
        static::assertInstanceOf(UPSShippingMethod::class, $method);
    }

    public function testHasShippingMethod()
    {
        static::assertTrue($this->provider->hasShippingMethod($this->enabledMethod->getIdentifier()));
    }

    public function testHasShippingMethodFalse()
    {
        static::assertFalse($this->provider->hasShippingMethod('wrong'));
    }
}
