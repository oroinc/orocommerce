<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Method\Factory\UPSShippingMethodTypeFactory;
use Oro\Bundle\UPSBundle\Method\Identifier\UPSMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

class UPSShippingMethodTypeFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UPSMethodTypeIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $typeIdentifierGenerator;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $methodIdentifierGenerator;

    /**
     * @var UPSTransport|\PHPUnit\Framework\MockObject\MockObject
     */
    private $transport;

    /**
     * @var PriceRequestFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceRequestFactory;

    /**
     * @var ShippingPriceCache|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingPriceCache;

    /**
     * @var UPSShippingMethodTypeFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $factory;

    protected function setUp(): void
    {
        $this->typeIdentifierGenerator = $this->createMock(UPSMethodTypeIdentifierGeneratorInterface::class);
        $this->methodIdentifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->transport = $this->createMock(UPSTransport::class);
        $this->priceRequestFactory = $this->createMock(PriceRequestFactory::class);
        $this->shippingPriceCache = $this->createMock(ShippingPriceCache::class);

        $this->factory = new UPSShippingMethodTypeFactory(
            $this->typeIdentifierGenerator,
            $this->methodIdentifierGenerator,
            $this->transport,
            $this->priceRequestFactory,
            $this->shippingPriceCache
        );
    }

    public function testCreate()
    {
        $identifier = 'ups_1_59';
        $methodId = 'ups_1';

        /** @var UPSSettings|\PHPUnit\Framework\MockObject\MockObject $settings */
        $settings = $this->createMock(UPSSettings::class);

        /** @var Channel|\PHPUnit\Framework\MockObject\MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects($this->any())
            ->method('getTransport')
            ->willReturn($settings);

        /** @var ShippingService|\PHPUnit\Framework\MockObject\MockObject $service */
        $service = $this->createMock(ShippingService::class);

        $service->expects($this->once())
            ->method('getDescription')
            ->willReturn('air');

        $this->methodIdentifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($methodId);

        $this->typeIdentifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel, $service)
            ->willReturn($identifier);

        $this->assertEquals(new UPSShippingMethodType(
            $identifier,
            'air',
            $methodId,
            $service,
            $settings,
            $this->transport,
            $this->priceRequestFactory,
            $this->shippingPriceCache
        ), $this->factory->create($channel, $service));
    }
}
