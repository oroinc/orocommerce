<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\ShippingBundle\Method\Factory\IntegrationShippingMethodFactoryInterface;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethod;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodProvider;
use Oro\Bundle\DPDBundle\Provider\ChannelType;
use Oro\Component\Testing\Unit\EntityTrait;

class DPDShippingMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var DPDShippingMethodProvider
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
     * @var DPDShippingMethod
     */
    private $method;

    public function setUp()
    {
        $repository = $this->createMock(ChannelRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityRepository')
            ->with('OroIntegrationBundle:Channel')
            ->willReturn($repository);

        $channel = $this->getEntity(Channel::class, [
            'id' => 10,
            'enabled' => true,
        ]);

        $repository->expects(static::once())
            ->method('findByType')
            ->with(ChannelType::TYPE)
            ->willReturn([$channel]);

        $this->method = $this->createMock(DPDShippingMethod::class);
        $this->method
            ->method('getIdentifier')
            ->willReturn('dpd_10');

        $this->methodFactory = $this->createMock(IntegrationShippingMethodFactoryInterface::class);

        $this->methodFactory->expects($this->once())
            ->method('create')
            ->with($channel)
            ->willReturn($this->method);

        $this->provider = new DPDShippingMethodProvider($this->doctrineHelper, $this->methodFactory);
    }

    public function testGetShippingMethods()
    {
        $methods = $this->provider->getShippingMethods();
        static::assertCount(1, $methods);
        $actualMethod = reset($methods);
        static::assertSame($this->method, $actualMethod);
    }

    public function testGetShippingMethod()
    {
        $method = $this->provider->getShippingMethod($this->method->getIdentifier());
        static::assertInstanceOf(DPDShippingMethod::class, $method);
    }

    public function testGetShippingMethodNull()
    {
        $method = $this->provider->getShippingMethod('wrong');
        static::assertNull($method);
    }

    public function testHasShippingMethod()
    {
        static::assertTrue($this->provider->hasShippingMethod($this->method->getIdentifier()));
    }

    public function testHasShippingMethodFalse()
    {
        static::assertFalse($this->provider->hasShippingMethod('wrong'));
    }
}
