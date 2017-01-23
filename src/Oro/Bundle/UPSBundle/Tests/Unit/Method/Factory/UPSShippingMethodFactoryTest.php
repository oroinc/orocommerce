<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Method\Factory\UPSShippingMethodFactory;
use Oro\Bundle\UPSBundle\Method\Factory\UPSShippingMethodTypeFactoryInterface;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

class UPSShippingMethodFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UPSTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transport;

    /**
     * @var PriceRequestFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceRequestFactory;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localizationHelper;

    /**
     * @var ShippingPriceCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingPriceCache;

    /**
     * @var IntegrationMethodIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodIdentifierGenerator;

    /**
     * @var UPSShippingMethodTypeFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $methodTypeFactory;

    /**
     * @var UPSShippingMethodFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $factory;

    protected function setUp()
    {
        $this->transport = $this->createMock(UPSTransport::class);
        $this->priceRequestFactory = $this->createMock(PriceRequestFactory::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->shippingPriceCache = $this->createMock(ShippingPriceCache::class);
        $this->methodIdentifierGenerator = $this->createMock(IntegrationMethodIdentifierGeneratorInterface::class);
        $this->methodTypeFactory = $this->createMock(UPSShippingMethodTypeFactoryInterface::class);

        $this->factory = new UPSShippingMethodFactory(
            $this->transport,
            $this->priceRequestFactory,
            $this->localizationHelper,
            $this->shippingPriceCache,
            $this->methodIdentifierGenerator,
            $this->methodTypeFactory
        );
    }

    public function testCreate()
    {
        $identifier = 'ups_1';
        $labelsCollection = $this->createMock(Collection::class);

        /** @var UPSSettings|\PHPUnit_Framework_MockObject_MockObject $settings */
        $settings = $this->createMock(UPSSettings::class);

        $settings->expects($this->once())
            ->method('getLabels')
            ->willReturn($labelsCollection);

        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects($this->any())
            ->method('getTransport')
            ->willReturn($settings);

        $type1 = $this->createMock(UPSShippingMethodType::class);
        $type2 = $this->createMock(UPSShippingMethodType::class);

        $service1 = $this->createMock(ShippingService::class);
        $service2 = $this->createMock(ShippingService::class);

        $this->methodTypeFactory->expects($this->at(0))
            ->method('create')
            ->with($channel, $service1)
            ->willReturn($type1);

        $this->methodTypeFactory->expects($this->at(1))
            ->method('create')
            ->with($channel, $service2)
            ->willReturn($type2);

        $serviceCollection = $this->createMock(Collection::class);
        $serviceCollection->expects($this->once())
            ->method('toArray')
            ->willReturn([$service1, $service2]);

        $settings->expects($this->once())
            ->method('getApplicableShippingServices')
            ->willReturn($serviceCollection);

        $this->methodIdentifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->with($labelsCollection)
            ->willReturn('en');

        $this->assertEquals(new UPSShippingMethod(
            $identifier,
            'en',
            [$type1, $type2],
            $settings,
            $this->transport,
            $this->priceRequestFactory,
            $this->shippingPriceCache
        ), $this->factory->create($channel));
    }
}
