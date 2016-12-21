<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCacheKey;
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
     * @var string
     */
    protected $methodId = 'shipping_method';

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

    /**
     * @var ShippingPriceCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

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

        $this->shippingService = $this->createMock(ShippingService::class);

        /** @var PriceRequestFactory | \PHPUnit_Framework_MockObject_MockObject $priceRequestFactory */
        $this->priceRequestFactory = $this->getMockBuilder(PriceRequestFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->cache = $this->getMockBuilder(ShippingPriceCache::class)
            ->disableOriginalConstructor()->getMock();

        $this->upsShippingMethodType = new UPSShippingMethodType(
            $this->methodId,
            $this->transport,
            $this->transportProvider,
            $this->shippingService,
            $this->priceRequestFactory,
            $this->cache
        );
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
        $context = $this->createMock(ShippingContextInterface::class);

        $methodOptions = ['surcharge' => $methodSurcharge];
        $this->shippingService->expects(self::any())->method('getCode')->willReturn('02');
        $typeOptions = ['surcharge' => $typeSurcharge];

        $priceRequest = $this->getMockBuilder(PriceRequest::class)->disableOriginalConstructor()->getMock();
        $priceRequest->expects(self::once())->method('getPackages')->willReturn([new Package(), new Package()]);

        $this->priceRequestFactory->expects(self::once())->method('create')->willReturn($priceRequest);

        $responsePrice = Price::create(50, 'USD');

        $priceResponse = $this->getMockBuilder(PriceResponse::class)->disableOriginalConstructor()->getMock();
        $priceResponse->expects(self::once())->method('getPriceByService')->willReturn($responsePrice);

        $this->transportProvider->expects(self::once())->method('getPriceResponse')->willReturn($priceResponse);

        $cacheKey = (new ShippingPriceCacheKey())->setTransport($this->transport)->setPriceRequest($priceRequest)
            ->setMethodId($this->methodId)->setTypeId($this->shippingService->getCode());

        $this->cache->expects(static::once())
            ->method('createKey')
            ->with($this->transport, $priceRequest, $this->methodId, $this->shippingService->getCode())
            ->willReturn($cacheKey);

        $this->cache->expects(static::once())
            ->method('containsPrice')
            ->with($cacheKey)
            ->willReturn(false);

        $this->cache->expects(static::once())
            ->method('savePrice')
            ->with($cacheKey, $responsePrice);

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

    public function testCalculatePriceCache()
    {
        /** @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->createMock(ShippingContextInterface::class);

        $methodOptions = ['surcharge' => 10];
        $this->shippingService->expects(self::any())->method('getCode')->willReturn('02');
        $typeOptions = ['surcharge' => 15];

        $priceRequest = $this->getMockBuilder(PriceRequest::class)->disableOriginalConstructor()->getMock();
        $priceRequest->expects(self::once())->method('getPackages')->willReturn([new Package(), new Package()]);

        $this->priceRequestFactory->expects(self::once())->method('create')->willReturn($priceRequest);

        $this->transportProvider->expects(self::never())->method('getPriceResponse');

        $cacheKey = (new ShippingPriceCacheKey())->setTransport($this->transport)->setPriceRequest($priceRequest)
            ->setMethodId($this->methodId)->setTypeId($this->shippingService->getCode());

        $this->cache->expects(static::once())
            ->method('createKey')
            ->with($this->transport, $priceRequest, $this->methodId, $this->shippingService->getCode())
            ->willReturn($cacheKey);

        $this->cache->expects(static::once())
            ->method('containsPrice')
            ->with($cacheKey)
            ->willReturn(true);

        $this->cache->expects(static::once())
            ->method('fetchPrice')
            ->with($cacheKey)
            ->willReturn(Price::create(5, 'USD'));

        $price = $this->upsShippingMethodType->calculatePrice($context, $methodOptions, $typeOptions);

        static::assertEquals(Price::create(30, 'USD'), $price);
    }
}
