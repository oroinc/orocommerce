<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod\Factory;

// phpcs:disable
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory\FedexShippingMethodTypeFactory;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethodType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\Identifier\FedexMethodTypeIdentifierGeneratorInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use PHPUnit\Framework\TestCase;

// phpcs:enable

class FedexShippingMethodTypeFactoryTest extends TestCase
{
    private const IDENTIFIER = 'id';
    private const LABEL = 'label';

    /** @var FedexMethodTypeIdentifierGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $identifierGenerator;

    /** @var FedexRateServiceRequestSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceRequestSettingsFactory;

    /** @var FedexRequestByRateServiceSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceRequestFactory;

    /** @var FedexRequestByRateServiceSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceRequestSoapFactory;

    /** @var FedexRateServiceBySettingsClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceClient;

    /** @var FedexShippingMethodTypeFactory */
    private $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->identifierGenerator = $this->createMock(FedexMethodTypeIdentifierGeneratorInterface::class);
        $this->rateServiceRequestSettingsFactory = $this->createMock(
            FedexRateServiceRequestSettingsFactoryInterface::class
        );
        $this->rateServiceRequestFactory = $this->createMock(FedexRequestByRateServiceSettingsFactoryInterface::class);
        $this->rateServiceRequestSoapFactory = $this->createMock(
            FedexRequestByRateServiceSettingsFactoryInterface::class
        );
        $this->rateServiceClient = $this->createMock(FedexRateServiceBySettingsClientInterface::class);

        $this->factory = new FedexShippingMethodTypeFactory(
            $this->identifierGenerator,
            $this->rateServiceRequestSettingsFactory,
            $this->rateServiceRequestFactory,
            $this->rateServiceRequestSoapFactory,
            $this->rateServiceClient
        );
    }

    public function testCreate()
    {
        $settings = new FedexIntegrationSettings();

        $channel = new Channel();
        $channel->setTransport($settings);

        $service = new FedexShippingService();
        $service->setDescription(self::LABEL);

        $this->identifierGenerator->expects(self::once())
            ->method('generate')
            ->with($service)
            ->willReturn(self::IDENTIFIER);

        self::assertEquals(
            new FedexShippingMethodType(
                $this->rateServiceRequestSettingsFactory,
                $this->rateServiceRequestFactory,
                $this->rateServiceClient,
                self::IDENTIFIER,
                $service,
                $settings
            ),
            $this->factory->create($channel, $service)
        );
    }
}
