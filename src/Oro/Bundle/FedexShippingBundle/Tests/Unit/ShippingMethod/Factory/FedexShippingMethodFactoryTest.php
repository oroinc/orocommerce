<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod\Factory;

// @codingStandardsIgnoreStart
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory\FedexShippingMethodFactory;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory\FedexShippingMethodTypeFactoryInterface;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Provider\IntegrationIconProviderInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use PHPUnit\Framework\TestCase;

// @codingStandardsIgnoreEnd

class FedexShippingMethodFactoryTest extends TestCase
{
    /** @var IntegrationIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $identifierGenerator;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var IntegrationIconProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $iconProvider;

    /** @var FedexShippingMethodTypeFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $typeFactory;

    /** @var FedexRateServiceRequestSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceRequestSettingsFactory;

    /** @var FedexRequestByRateServiceSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceRequestFactory;

    /** @var FedexRateServiceBySettingsClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceClient;

    /** @var FedexShippingMethodFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->identifierGenerator = $this->createMock(IntegrationIdentifierGeneratorInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->iconProvider = $this->createMock(IntegrationIconProviderInterface::class);
        $this->typeFactory = $this->createMock(FedexShippingMethodTypeFactoryInterface::class);
        $this->rateServiceRequestSettingsFactory = $this->createMock(
            FedexRateServiceRequestSettingsFactoryInterface::class
        );
        $this->rateServiceRequestFactory = $this->createMock(FedexRequestByRateServiceSettingsFactoryInterface::class);
        $this->rateServiceClient = $this->createMock(FedexRateServiceBySettingsClientInterface::class);

        $this->factory = new FedexShippingMethodFactory(
            $this->identifierGenerator,
            $this->localizationHelper,
            $this->iconProvider,
            $this->typeFactory,
            $this->rateServiceRequestSettingsFactory,
            $this->rateServiceRequestFactory,
            $this->rateServiceClient
        );
    }

    public function testCreate(): void
    {
        $identifier = 'id';
        $label = 'label';
        $iconUri = 'icon';
        $enabled = true;

        $services = new ArrayCollection([
            new FedexShippingService(),
            new FedexShippingService(),
        ]);
        $transport = new FedexIntegrationSettings();
        $transport->addShippingService($services[0]);
        $transport->addShippingService($services[1]);

        $channel = new Channel();
        $channel->setTransport($transport);
        $channel->setEnabled($enabled);

        $this->identifierGenerator->expects(self::once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->with($transport->getLabels())
            ->willReturn($label);

        $this->iconProvider->expects(self::once())
            ->method('getIcon')
            ->with($channel)
            ->willReturn($iconUri);

        $types = [
            $this->createMock(ShippingMethodTypeInterface::class),
            $this->createMock(ShippingMethodTypeInterface::class),
        ];
        $this->typeFactory->expects(self::exactly(2))
            ->method('create')
            ->withConsecutive([$channel, $services[0]], [$channel, $services[1]])
            ->willReturnOnConsecutiveCalls($types[0], $types[1]);

        $expected = new FedexShippingMethod(
            $this->rateServiceRequestSettingsFactory,
            $this->rateServiceRequestFactory,
            $this->rateServiceClient,
            $identifier,
            $label,
            $iconUri,
            $enabled,
            $transport,
            $types
        );
        self::assertEquals($expected, $this->factory->create($channel));
    }
}
