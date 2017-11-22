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
    const IDENTIFIER = 'id';
    const LABEL = 'label';
    const ICON_PATH = 'path';
    const ENABLED = true;

    /**
     * @var IntegrationIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $identifierGenerator;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $localizationHelper;

    /**
     * @var IntegrationIconProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $iconProvider;

    /**
     * @var FedexShippingMethodTypeFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $typeFactory;

    /**
     * @var FedexRateServiceRequestSettingsFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rateServiceRequestSettingsFactory;

    /**
     * @var FedexRequestByRateServiceSettingsFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rateServiceRequestFactory;

    /**
     * @var FedexRateServiceBySettingsClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rateServiceClient;

    /**
     * @var FedexShippingMethodFactory
     */
    private $factory;

    protected function setUp()
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

    public function testCreate()
    {
        $services = new ArrayCollection(
            [
                new FedexShippingService(),
                new FedexShippingService(),
            ]
        );
        $settings = new FedexIntegrationSettings();
        $settings
            ->addShippingService($services[0])
            ->addShippingService($services[1]);

        $channel = new Channel();
        $channel
            ->setTransport($settings)
            ->setEnabled(self::ENABLED);

        $this->identifierGenerator
            ->expects(static::once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn(self::IDENTIFIER);

        $this->localizationHelper
            ->expects(static::once())
            ->method('getLocalizedValue')
            ->with($settings->getLabels())
            ->willReturn(self::LABEL);

        $this->iconProvider
            ->expects(static::once())
            ->method('getIcon')
            ->with($channel)
            ->willReturn(self::ICON_PATH);

        $types = [
            $this->createMock(ShippingMethodTypeInterface::class),
            $this->createMock(ShippingMethodTypeInterface::class),
        ];
        $this->typeFactory
            ->expects(static::exactly(2))
            ->method('create')
            ->withConsecutive([$channel, $services[0]], [$channel, $services[1]])
            ->willReturnOnConsecutiveCalls($types[0], $types[1]);

        static::assertEquals(
            new FedexShippingMethod(
                $this->rateServiceRequestSettingsFactory,
                $this->rateServiceRequestFactory,
                $this->rateServiceClient,
                self::IDENTIFIER,
                self::LABEL,
                self::ICON_PATH,
                self::ENABLED,
                $settings,
                $types
            ),
            $this->factory->create($channel)
        );
    }
}
