<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceResponse;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;

class UPSShippingMethodTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    /**
     * @var UPSTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var UPSTransportProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transportProvider;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingService;

    /**
     * @var PriceRequestFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRequestFactory;

    /**
     * @var UPSShippingMethodType
     */
    protected $upsShippingMethodType;

    protected function setUp()
    {
        $this->transport = $this->getEntity(
            UPSTransport::class,
            [
                'baseUrl' => 'some url',
                'apiUser' => 'some user',
                'apiPassword' => 'some password',
                'apiKey' => 'some key',
                'shippingAccountNumber' => 'some number',
                'shippingAccountName' => 'some name',
                'pickupType' => '01',
                'unitOfWeight' => 'LPS',
                'country' => new Country('US'),
                'applicableShippingServices' => [new ShippingService()]
            ]
        );

        $this->transportProvider = $this->getMockBuilder(UPSTransportProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shippingService = $this->getMock(ShippingService::class);

        /** @var PriceRequestFactory | \PHPUnit_Framework_MockObject_MockObject $priceRequestFactory */
        $this->priceRequestFactory = $this->getMockBuilder(PriceRequestFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->upsShippingMethodType = new UPSShippingMethodType(
            $this->transport,
            $this->transportProvider,
            $this->shippingService,
            $this->priceRequestFactory
        );
    }

    public function testSetIdentifier()
    {
        $identifier = 'ups_1';
        $this->upsShippingMethodType->setIdentifier($identifier);

        static::assertEquals($identifier, $this->upsShippingMethodType->getIdentifier());
    }

    public function testSetLabel()
    {
        $label = 'ups 1';
        $this->upsShippingMethodType->setLabel($label);

        static::assertEquals($label, $this->upsShippingMethodType->getLabel());
    }

    public function testGetOptionsConfigurationFormType()
    {
        static::assertEquals(
            UPSShippingMethodOptionsType::class,
            $this->upsShippingMethodType->getOptionsConfigurationFormType()
        );
    }

    public function testGetSortOrder()
    {
        static::assertEquals(0, $this->upsShippingMethodType->getSortOrder());
    }

    /**
     * @param int $methodSurcharge
     * @param int $typeSurcharge
     * @param int $expectedPrice
     *
     * @dataProvider calculatePriceDataProvider
     */
    public function testCalculatePrice($methodSurcharge, $typeSurcharge, $expectedPrice)
    {
        /** @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(ShippingContextInterface::class);

        $methodOptions = ['surcharge' => $methodSurcharge];
        $this->shippingService->expects(self::any())->method('getCode')->willReturn('02');
        $typeOptions = ['surcharge' => $typeSurcharge];

        $priceRequest = $this->getMockBuilder(PriceRequest::class)->disableOriginalConstructor()->getMock();
        $priceRequest->expects(self::once())->method('getPackages')->willReturn([new Package(), new Package()]);

        $this->priceRequestFactory->expects(self::once())->method('create')->willReturn($priceRequest);

        $priceResponse = $this->getMockBuilder(PriceResponse::class)->disableOriginalConstructor()->getMock();
        $priceResponse->expects(self::once())->method('getPriceByService')->willReturn(Price::create(50, 'USD'));

        $this->transportProvider->expects(self::once())->method('getPrices')->willReturn($priceResponse);

        $price = $this->upsShippingMethodType->calculatePrice($context, $methodOptions, $typeOptions);

        static::assertEquals(Price::create($expectedPrice, 'USD'), $price);
    }

    /**
     * @return array
     */
    public function calculatePriceDataProvider()
    {
        return [
            'TypeSurcharge' => [
                'methodSurcharge' => 0,
                'typeSurcharge' => 5,
                'expectedPrice' => 55
            ],
            'MethodSurcharge' => [
                'methodSurcharge' => 3,
                'typeSurcharge' => 0,
                'expectedPrice' => 53
            ],
            'Method&TypeSurcharge' => [
                'methodSurcharge' => 3,
                'typeSurcharge' => 5,
                'expectedPrice' => 58
            ],
            'NoSurcharge' => [
                'methodSurcharge' => 0,
                'typeSurcharge' => 0,
                'expectedPrice' => 50
            ]
        ];
    }
}
