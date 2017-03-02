<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Method;

use Oro\Bundle\DPDBundle\Provider\PackageProvider;
use Oro\Bundle\DPDBundle\Provider\RateProvider;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Bundle\DPDBundle\Model\Package;
use Oro\Bundle\DPDBundle\Provider\DPDTransport as DPDTransportProvider;
use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodType;

class DPDShippingMethodTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @internal
     */
    const IDENTIFIER = '02';

    /**
     * @internal
     */
    const LABEL = 'service_code_label';

    /**
     * @var string
     */
    protected $methodId = 'shipping_method';

    /**
     * @var DPDTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var DPDTransportProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transportProvider;

    /**
     * @var PackageProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $packageProvider;

    /**
     * @var RateProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rateProvider;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingService;

    /**
     * @var DPDShippingMethodType
     */
    protected $dpdShippingMethodType;

    protected function setUp()
    {
        $this->transport = $this->getEntity(
            DPDTransport::class,
            [
                'liveMode' => false,
                'cloudUserId' => 'some cloud user id',
                'cloudUserToken' => 'some cloud user token',
                'unitOfWeight' => ((new WeightUnit())->setCode('kg')),
                'ratePolicy' => DPDTransport::FLAT_RATE_POLICY,
                'flatRatePriceValue' => '50.000',
                'labelSize' => DPDTransport::PDF_A4_LABEL_SIZE,
                'labelStartPosition' => DPDTransport::UPPERLEFT_LABEL_START_POSITION,
                'invalidate_cache_at' => new \DateTime('2020-01-01'),
                'applicableShippingServices' => [new ShippingService()],
            ]
        );

        $this->transportProvider = $this->createMock(DPDTransportProvider::class);

        $this->shippingService = $this->createMock(ShippingService::class);

        $this->packageProvider = $this->createMock(PackageProvider::class);

        $this->rateProvider = $this->createMock(RateProvider::class);

        $this->dpdShippingMethodType = new DPDShippingMethodType(
            self::IDENTIFIER,
            self::LABEL,
            $this->methodId,
            $this->shippingService,
            $this->transport,
            $this->transportProvider,
            $this->packageProvider,
            $this->rateProvider
        );
    }

    public function testGetOptionsConfigurationFormType()
    {
        static::assertEquals(
            DPDShippingMethodOptionsType::class,
            $this->dpdShippingMethodType->getOptionsConfigurationFormType()
        );
    }

    public function testGetSortOrder()
    {
        static::assertEquals(0, $this->dpdShippingMethodType->getSortOrder());
    }

    /**
     * @param $ratePrice
     * @param int $methodHandlingFee
     * @param int $typeHandlingFee
     * @param int $expectedPrice
     *
     * @dataProvider calculatePriceDataProvider
     */
    public function testCalculatePrice($ratePrice, $methodHandlingFee, $typeHandlingFee, $expectedPrice)
    {
        /** @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject $context * */
        $context = $this->createMock(ShippingContextInterface::class);
        $lineItems = $this->createMock(ShippingLineItemCollectionInterface::class);
        $context->expects(self::once())->method('getLineItems')->willReturn($lineItems);
        $shippingAddress = $this->createMock(AddressInterface::class);
        $context->expects(self::any())->method('getShippingAddress')->willReturn($shippingAddress);
        $context->expects(self::once())->method('getCurrency')->willReturn('USD');

        $methodOptions = ['handling_fee' => $methodHandlingFee];
        $this->shippingService->expects(self::any())->method('getCode')->willReturn(self::IDENTIFIER);
        $typeOptions = ['handling_fee' => $typeHandlingFee];

        $this->packageProvider->expects(self::once())->method('createPackages')->willReturn([new Package()]);
        $this->rateProvider->expects(self::once())->method('getRateValue')->willReturn($ratePrice);

        $price = $this->dpdShippingMethodType->calculatePrice($context, $methodOptions, $typeOptions);

        static::assertEquals(Price::create($expectedPrice, 'USD'), $price);
    }

    /**
     * @return array
     */
    public function calculatePriceDataProvider()
    {
        return [
            'TypeSurcharge' => [
                'ratePrice' => 50,
                'methodHandlingFee' => 0,
                'typeHandlingFee' => 5,
                'expectedPrice' => 55,
            ],
            'MethodSurcharge' => [
                'ratePrice' => 50,
                'methodHandlingFee' => 3,
                'typeHandlingFee' => 0,
                'expectedPrice' => 53,
            ],
            'Method&TypeSurcharge' => [
                'ratePrice' => 50,
                'methodHandlingFee' => 3,
                'typeHandlingFee' => 5,
                'expectedPrice' => 58,
            ],
            'NoSurcharge' => [
                'ratePrice' => 50,
                'methodHandlingFee' => 0,
                'typeHandlingFee' => 0,
                'expectedPrice' => 50,
            ],
        ];
    }
}
