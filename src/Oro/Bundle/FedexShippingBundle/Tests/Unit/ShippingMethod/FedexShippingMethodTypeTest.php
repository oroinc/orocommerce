<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\ShippingMethod;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestByContextAndSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethodType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use PHPUnit\Framework\TestCase;

class FedexShippingMethodTypeTest extends TestCase
{
    const IDENTIFIER = 'id';
    const LABEL = 'label';

    /**
     * @var FedexRequestByContextAndSettingsFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rateServiceRequestFactory;

    /**
     * @var FedexRateServiceBySettingsClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rateServiceClient;

    protected function setUp()
    {
        $this->rateServiceRequestFactory = $this->createMock(FedexRequestByContextAndSettingsFactoryInterface::class);
        $this->rateServiceClient = $this->createMock(FedexRateServiceBySettingsClientInterface::class);
    }

    public function testGetters()
    {
        $type = $this->createShippingMethodType(new FedexIntegrationSettings());

        static::assertSame(self::IDENTIFIER, $type->getIdentifier());
        static::assertSame(self::LABEL, $type->getLabel());
        static::assertSame(0, $type->getSortOrder());
        static::assertSame(FedexShippingMethodOptionsType::class, $type->getOptionsConfigurationFormType());
    }

    public function testCalculatePriceNoRequest()
    {
        $settings = new FedexIntegrationSettings();
        $context = $this->createMock(ShippingContextInterface::class);

        $this->rateServiceRequestFactory
            ->expects(static::once())
            ->method('create')
            ->with($settings, $context)
            ->willReturn(null);

        $this->rateServiceClient
            ->expects(static::never())
            ->method('send');

        static::assertNull($this->createShippingMethodType($settings)->calculatePrice($context, [], []));
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

        $context = $this->createMock(ShippingContextInterface::class);
        $type = $this->createShippingMethodType($settings);

        $this->rateServiceRequestFactory
            ->expects(static::once())
            ->method('create')
            ->with($settings, $context)
            ->willReturn($request);

        $this->rateServiceClient
            ->expects(static::once())
            ->method('send')
            ->with($request, $settings)
            ->willReturn($response);

        static::assertNull($type->calculatePrice($context, [], []));
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

        $context = $this->createMock(ShippingContextInterface::class);
        $type = $this->createShippingMethodType($settings);

        $this->rateServiceRequestFactory
            ->expects(static::once())
            ->method('create')
            ->with($settings, $context)
            ->willReturn($request);

        $this->rateServiceClient
            ->expects(static::once())
            ->method('send')
            ->with($request, $settings)
            ->willReturn($response);

        static::assertEquals(
            Price::create(18.4, 'USD'),
            $type->calculatePrice(
                $context,
                [FedexShippingMethod::OPTION_SURCHARGE => 1.1],
                [FedexShippingMethod::OPTION_SURCHARGE => 3.2]
            )
        );
    }

    /**
     * @param FedexIntegrationSettings $settings
     *
     * @return FedexShippingMethodType
     */
    private function createShippingMethodType(FedexIntegrationSettings $settings): FedexShippingMethodType
    {
        return new FedexShippingMethodType(
            $this->rateServiceRequestFactory,
            $this->rateServiceClient,
            self::IDENTIFIER,
            self::LABEL,
            $settings
        );
    }
}
