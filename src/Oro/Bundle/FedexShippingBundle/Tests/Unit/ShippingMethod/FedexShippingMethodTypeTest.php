<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettingsInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethodType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

// @codingStandardsIgnoreEnd

class FedexShippingMethodTypeTest extends \PHPUnit\Framework\TestCase
{
    private const IDENTIFIER = 'id';
    private const LABEL = 'label';

    /** @var FedexRateServiceRequestSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceRequestSettingsFactory;

    /** @var FedexRequestByRateServiceSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceRequestFactory;

    /** @var FedexRateServiceBySettingsClientInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $rateServiceClient;

    protected function setUp(): void
    {
        $this->rateServiceRequestSettingsFactory = $this->createMock(
            FedexRateServiceRequestSettingsFactoryInterface::class
        );
        $this->rateServiceRequestFactory = $this->createMock(FedexRequestByRateServiceSettingsFactoryInterface::class);
        $this->rateServiceClient = $this->createMock(FedexRateServiceBySettingsClientInterface::class);
    }

    public function testGetters()
    {
        $service = new FedexShippingService();
        $service->setDescription(self::LABEL);

        $type = $this->createShippingMethodType(new FedexIntegrationSettings(), $service);

        self::assertSame(self::IDENTIFIER, $type->getIdentifier());
        self::assertSame(self::LABEL, $type->getLabel());
        self::assertSame(0, $type->getSortOrder());
        self::assertSame(FedexShippingMethodOptionsType::class, $type->getOptionsConfigurationFormType());
    }

    public function testCalculatePriceNoRequest()
    {
        $settings = new FedexIntegrationSettings();
        $context = $this->createMock(ShippingContextInterface::class);
        $rule = new ShippingServiceRule();
        $requestSettings = $this->createMock(FedexRateServiceRequestSettingsInterface::class);

        $this->rateServiceRequestSettingsFactory->expects(self::once())
            ->method('create')
            ->with($settings, $context, $rule)
            ->willReturn($requestSettings);

        $this->rateServiceRequestFactory->expects(self::once())
            ->method('create')
            ->with($requestSettings)
            ->willReturn(null);

        $this->rateServiceClient->expects(self::never())
            ->method('send');

        self::assertNull(
            $this->createShippingMethodType($settings, $this->createShippingService('', $rule))
                ->calculatePrice($context, [], [])
        );
    }

    public function testCalculatePricesHasNoNeededPrice()
    {
        $prices = [
            'other' => Price::create(1, ''),
            'other2' => Price::create(2, ''),
        ];
        $settings = new FedexIntegrationSettings();
        $request = new FedexRequest();
        $response = new FedexRateServiceResponse('', 0, $prices);
        $rule = new ShippingServiceRule();
        $requestSettings = $this->createMock(FedexRateServiceRequestSettingsInterface::class);

        $context = $this->createMock(ShippingContextInterface::class);
        $type = $this->createShippingMethodType($settings, $this->createShippingService('', $rule));

        $this->rateServiceRequestSettingsFactory->expects(self::once())
            ->method('create')
            ->with($settings, $context, $rule)
            ->willReturn($requestSettings);

        $this->rateServiceRequestFactory->expects(self::once())
            ->method('create')
            ->with($requestSettings)
            ->willReturn($request);

        $this->rateServiceClient->expects(self::once())
            ->method('send')
            ->with($request, $settings)
            ->willReturn($response);

        self::assertNull($type->calculatePrice($context, [], []));
    }

    public function testCalculatePrice()
    {
        $prices = [
            'other' => Price::create(1, ''),
            'other2' => Price::create(2, ''),
            self::IDENTIFIER => Price::create(14.1, 'USD'),
        ];
        $settings = new FedexIntegrationSettings();
        $request = new FedexRequest();
        $response = new FedexRateServiceResponse('', 0, $prices);
        $rule = new ShippingServiceRule();
        $requestSettings = $this->createMock(FedexRateServiceRequestSettingsInterface::class);

        $context = $this->createMock(ShippingContextInterface::class);
        $type = $this->createShippingMethodType($settings, $this->createShippingService(self::IDENTIFIER, $rule));

        $this->rateServiceRequestSettingsFactory->expects(self::once())
            ->method('create')
            ->with($settings, $context, $rule)
            ->willReturn($requestSettings);

        $this->rateServiceRequestFactory->expects(self::once())
            ->method('create')
            ->with($requestSettings)
            ->willReturn($request);

        $this->rateServiceClient->expects(self::once())
            ->method('send')
            ->with($request, $settings)
            ->willReturn($response);

        self::assertEquals(
            Price::create(18.4, 'USD'),
            $type->calculatePrice(
                $context,
                [FedexShippingMethod::OPTION_SURCHARGE => 1.1],
                [FedexShippingMethod::OPTION_SURCHARGE => 3.2]
            )
        );
    }

    private function createShippingMethodType(
        FedexIntegrationSettings $settings,
        FedexShippingService $service
    ): FedexShippingMethodType {
        return new FedexShippingMethodType(
            $this->rateServiceRequestSettingsFactory,
            $this->rateServiceRequestFactory,
            $this->rateServiceClient,
            self::IDENTIFIER,
            $service,
            $settings
        );
    }

    private function createShippingService(string $code, ShippingServiceRule $rule): FedexShippingService
    {
        $service = new FedexShippingService();
        $service->setCode($code);
        $service->setRule($rule);

        return $service;
    }
}
