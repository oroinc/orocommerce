<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Method\Factory\UPSShippingMethodFactory;
use Oro\Bundle\UPSBundle\Method\Factory\UPSShippingMethodTypeFactoryInterface;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

class UPSShippingMethodFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var UPSTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var PriceRequestFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $priceRequestFactory;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var ShippingPriceCache|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPriceCache;

    /** @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $integrationIdentifierGenerator;

    /** @var UPSShippingMethodTypeFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $methodTypeFactory;

    /** @var IntegrationIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $integrationIconProvider;

    /** @var UPSShippingMethodFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->transport = $this->createMock(UPSTransport::class);
        $this->priceRequestFactory = $this->createMock(PriceRequestFactory::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->shippingPriceCache = $this->createMock(ShippingPriceCache::class);
        $this->integrationIdentifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->methodTypeFactory = $this->createMock(UPSShippingMethodTypeFactoryInterface::class);
        $this->integrationIconProvider = $this->createMock(IntegrationIconProviderInterface::class);

        $this->factory = new UPSShippingMethodFactory(
            $this->transport,
            $this->priceRequestFactory,
            $this->localizationHelper,
            $this->integrationIconProvider,
            $this->shippingPriceCache,
            $this->integrationIdentifierGenerator,
            $this->methodTypeFactory
        );
    }

    public function testCreate(): void
    {
        $identifier = 'ups_1';
        $enabled = true;
        $label = 'label';
        $iconUri = 'bundles/icon-uri.png';

        $transport = $this->createMock(UPSSettings::class);

        $channel = new Channel();
        $channel->setTransport($transport);
        $channel->setEnabled($enabled);

        $this->integrationIconProvider->expects(self::once())
            ->method('getIcon')
            ->with($channel)
            ->willReturn($iconUri);

        $service1 = $this->createMock(ShippingService::class);
        $service2 = $this->createMock(ShippingService::class);

        $serviceCollection = $this->createMock(Collection::class);
        $serviceCollection->expects(self::once())
            ->method('toArray')
            ->willReturn([$service1, $service2]);

        $type1 = $this->createMock(UPSShippingMethodType::class);
        $type2 = $this->createMock(UPSShippingMethodType::class);

        $this->methodTypeFactory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive([$channel, $service1], [$channel, $service2])
            ->willReturnOnConsecutiveCalls($type1, $type2);

        $transport->expects(self::once())
            ->method('getApplicableShippingServices')
            ->willReturn($serviceCollection);

        $this->integrationIdentifierGenerator->expects(self::once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $labelsCollection = $this->createMock(Collection::class);
        $transport->expects(self::once())
            ->method('getLabels')
            ->willReturn($labelsCollection);

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->with($labelsCollection)
            ->willReturn($label);

        $expected = new UPSShippingMethod(
            $identifier,
            $label,
            $iconUri,
            [$type1, $type2],
            $transport,
            $this->transport,
            $this->priceRequestFactory,
            $this->shippingPriceCache,
            $enabled
        );
        self::assertEquals($expected, $this->factory->create($channel));
    }
}
