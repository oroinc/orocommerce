<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCacheKey;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Model\PriceResponse;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UPSShippingMethodTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UPSTransportProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transportProvider;

    /**
     * @var UPSTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var Channel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $channel;

    /**
     * @var PriceRequestFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRequestFactory;

    /**
     * @var UPSShippingMethod
     */
    protected $upsShippingMethod;

    /**
     * @var LocalizationHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $localizationHelper;

    /**
     * @var ShippingPriceCache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    protected function setUp()
    {
        $this->transportProvider = $this->getMockBuilder(UPSTransportProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $shippingService = $this->getEntity(
            ShippingService::class,
            ['id' => 1, 'code' => 'ups_identifier', 'description' => 'ups_label', 'country' => new Country('US')]
        );
        
        /** @var PriceRequestFactory | \PHPUnit_Framework_MockObject_MockObject $priceRequestFactory */
        $this->priceRequestFactory = $this->getMockBuilder(PriceRequestFactory::class)
            ->disableOriginalConstructor()->getMock();

        $this->transport = $this->getMockBuilder(UPSTransport::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transport->expects(self::any())->method('getApplicableShippingServices')->willReturn([$shippingService]);

        $this->channel = $this->getEntity(
            Channel::class,
            ['id' => 1, 'name' => 'ups_channel_1', 'transport' => $this->transport]
        );

        $this->localizationHelper = $this
            ->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache = $this
            ->getMockBuilder(ShippingPriceCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->upsShippingMethod =
            new UPSShippingMethod(
                $this->transportProvider,
                $this->channel,
                $this->priceRequestFactory,
                $this->localizationHelper,
                $this->cache
            );
    }

    public function testIsGrouped()
    {
        static::assertTrue($this->upsShippingMethod->isGrouped());
    }

    public function testGetIdentifier()
    {
        static::assertEquals('ups_1', $this->upsShippingMethod->getIdentifier());
    }

    public function testGetLabel()
    {
        $this->transport
            ->expects(self::any())
            ->method('getLabels')->willReturn(new ArrayCollection());

        $this->localizationHelper
            ->expects(self::once())
            ->method('getLocalizedValue')->willReturn('ups_channel_1');
        static::assertEquals('ups_channel_1', $this->upsShippingMethod->getLabel());
    }

    public function testGetTypes()
    {
        $types = $this->upsShippingMethod->getTypes();

        static::assertCount(1, $types);
        static::assertEquals('ups_identifier', $types[0]->getIdentifier());
    }

    public function testGetType()
    {
        $identifier = 'ups_identifier';
        $type = $this->upsShippingMethod->getType($identifier);

        static::assertInstanceOf(UPSShippingMethodType::class, $type);
        static::assertEquals('ups_identifier', $type->getIdentifier());
    }

    public function testGetOptionsConfigurationFormType()
    {
        $type = $this->upsShippingMethod->getOptionsConfigurationFormType();

        static::assertEquals(UPSShippingMethodOptionsType::class, $type);
    }

    public function testGetSortOrder()
    {
        static::assertEquals('20', $this->upsShippingMethod->getSortOrder());
    }


    /**
     * @param int $methodSurcharge
     * @param int $typeSurcharge
     * @param int $expectedPrice
     *
     * @dataProvider calculatePricesDataProvider
     */
    public function testCalculatePrices($methodSurcharge, $typeSurcharge, $expectedPrice)
    {
        /** @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(ShippingContextInterface::class);

        $methodOptions = ['surcharge' => $methodSurcharge];
        $optionsByTypes = ['01' => ['surcharge' => $typeSurcharge]];

        $priceRequest = $this->getMockBuilder(PriceRequest::class)->disableOriginalConstructor()->getMock();

        $this->priceRequestFactory->expects(self::once())->method('create')->willReturn($priceRequest);

        $this->transportProvider->expects(self::never())->method('getPriceResponse');

        $cacheKey = (new ShippingPriceCacheKey())->setTransport($this->transport)->setPriceRequest($priceRequest)
            ->setMethodId($this->upsShippingMethod->getIdentifier())->setTypeId(null);

        $this->cache->expects(static::once())
            ->method('createKey')
            ->with($this->transport, $priceRequest, $this->upsShippingMethod->getIdentifier(), null)
            ->willReturn($cacheKey);

        $this->cache->expects(static::once())
            ->method('containsPrice')
            ->with($cacheKey)
            ->willReturn(true);

        $this->cache->expects(static::once())
            ->method('fetchPrice')
            ->with($cacheKey)
            ->willReturn(Price::create(50, 'USD'));

        $prices = $this->upsShippingMethod->calculatePrices($context, $methodOptions, $optionsByTypes);

        static::assertCount(1, $prices);
        static::assertTrue(array_key_exists('01', $prices));
        static::assertEquals(Price::create($expectedPrice, 'USD'), $prices['01']);
    }

    /**
     * @return array
     */
    public function calculatePricesDataProvider()
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

    /**
     * @param string $number
     * @param string|null $resultURL
     *
     * @dataProvider trackingDataProvider
     */
    public function testGetTrackingLink($number, $resultURL)
    {
        static::assertEquals($resultURL, $this->upsShippingMethod->getTrackingLink($number));
    }

    /**
     * @return array
     */
    public function trackingDataProvider()
    {
        return [
            'emptyTrackingNumber' => [
                'number' => '',
                'resultURL' => null,
            ],
            'wrongTrackingNumber2' => [
                'number' => '123123123123',
                'resultURL' => null,
            ],
            'rightTrackingNumber' => [
                'number' => '1Z111E111111111111',
                'resultURL' => UPSShippingMethod::TRACKING_URL.'1Z111E111111111111',
            ],
        ];
    }

    public function testCalculatePricesWithoutCache()
    {
        /** @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(ShippingContextInterface::class);

        $methodOptions = ['surcharge' => 10];
        $optionsByTypes = [
            '01' => ['surcharge' => 20],
            '02' => ['surcharge' => 30],
            '03' => ['surcharge' => 40],
            '04' => ['surcharge' => 50],
        ];

        $priceRequest = $this->getMockBuilder(PriceRequest::class)->disableOriginalConstructor()->getMock();

        $this->priceRequestFactory->expects(self::once())->method('create')->willReturn($priceRequest);

        $cacheKey = (new ShippingPriceCacheKey())->setTransport($this->transport)->setPriceRequest($priceRequest)
            ->setMethodId($this->upsShippingMethod->getIdentifier());

        $this->cache->expects(static::at(0))
            ->method('createKey')
            ->with($this->transport, $priceRequest, $this->upsShippingMethod->getIdentifier(), null)
            ->willReturn($cacheKey);

        $this->cache->expects(static::at(1))
            ->method('containsPrice')
            ->with($cacheKey->setTypeId('01'))
            ->willReturn(true);

        $this->cache->expects(static::at(2))
            ->method('fetchPrice')
            ->with($cacheKey)
            ->willReturn(Price::create(60, 'USD'));

        $this->cache->expects(static::at(3))
            ->method('containsPrice')
            ->with($cacheKey->setTypeId('02'))
            ->willReturn(true);

        $this->cache->expects(static::at(4))
            ->method('fetchPrice')
            ->with($cacheKey)
            ->willReturn(Price::create(70, 'USD'));

        $this->cache->expects(static::at(5))
            ->method('containsPrice')
            ->with($cacheKey->setTypeId('03'))
            ->willReturn(false);

        $this->cache->expects(static::at(6))
            ->method('containsPrice')
            ->with($cacheKey->setTypeId('04'))
            ->willReturn(false);

        $priceResponse = $this->getMockBuilder(PriceResponse::class)->disableOriginalConstructor()->getMock();
        $priceResponse->expects(self::at(0))
            ->method('getPriceByService')
            ->with('03')
            ->willReturn(Price::create(80, 'USD'));

        $priceResponse->expects(self::at(1))
            ->method('getPriceByService')
            ->with('04')
            ->willReturn(Price::create(90, 'USD'));

        $this->transportProvider->expects(self::once())->method('getPriceResponse')->willReturn($priceResponse);

        $this->cache->expects(static::at(7))
            ->method('savePrice')
            ->with($cacheKey->setTypeId('03'))
            ->willReturn(Price::create(80, 'USD'));

        $this->cache->expects(static::at(8))
            ->method('savePrice')
            ->with($cacheKey->setTypeId('03'))
            ->willReturn(Price::create(90, 'USD'));

        $prices = $this->upsShippingMethod->calculatePrices($context, $methodOptions, $optionsByTypes);

        static::assertEquals([
            '01' => Price::create(90, 'USD'),
            '02' => Price::create(110, 'USD'),
            '03' => Price::create(130, 'USD'),
            '04' => Price::create(150, 'USD'),
        ], $prices);
    }

    public function testCalculatePricesOneWithoutCache()
    {
        /** @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject $context **/
        $context = $this->getMock(ShippingContextInterface::class);

        $methodOptions = ['surcharge' => 10];
        $optionsByTypes = [
            '01' => ['surcharge' => 20],
            '02' => ['surcharge' => 30],
        ];

        $priceRequest = $this->getMockBuilder(PriceRequest::class)->disableOriginalConstructor()->getMock();

        $this->priceRequestFactory->expects(self::once())->method('create')->willReturn($priceRequest);

        $cacheKey = (new ShippingPriceCacheKey())->setTransport($this->transport)->setPriceRequest($priceRequest)
            ->setMethodId($this->upsShippingMethod->getIdentifier());

        $this->cache->expects(static::at(0))
            ->method('createKey')
            ->with($this->transport, $priceRequest, $this->upsShippingMethod->getIdentifier(), null)
            ->willReturn($cacheKey);

        $this->cache->expects(static::at(1))
            ->method('containsPrice')
            ->with($cacheKey->setTypeId('01'))
            ->willReturn(true);

        $this->cache->expects(static::at(2))
            ->method('fetchPrice')
            ->with($cacheKey)
            ->willReturn(Price::create(60, 'USD'));

        $this->cache->expects(static::at(3))
            ->method('containsPrice')
            ->with($cacheKey->setTypeId('02'))
            ->willReturn(false);

        $priceResponse = $this->getMockBuilder(PriceResponse::class)->disableOriginalConstructor()->getMock();
        $priceResponse->expects(self::at(0))
            ->method('getPriceByService')
            ->with('02')
            ->willReturn(Price::create(70, 'USD'));

        $this->transport = $this->getMockBuilder(UPSTransport::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transport->expects(static::exactly(2))
            ->method('getApplicableShippingServices')
            ->willReturn([
                $this->getEntity(ShippingService::class, [
                    'code' => '01'
                ]),
                $this->getEntity(ShippingService::class, [
                    'code' => '02',
                    'description' => 'Air',
                ]),
            ]);
        $this->channel = $this->getEntity(
            Channel::class,
            ['id' => 1, 'transport' => $this->transport]
        );

        $priceRequest->expects(self::once())
            ->method('setServiceCode')
            ->with('02')
            ->willReturn($priceRequest);

        $priceRequest->expects(self::once())
            ->method('setServiceDescription')
            ->with('Air')
            ->willReturn($priceRequest);

        $this->transportProvider->expects(self::once())->method('getPriceResponse')->willReturn($priceResponse);

        $this->cache->expects(static::at(4))
            ->method('savePrice')
            ->with($cacheKey->setTypeId('02'))
            ->willReturn(Price::create(70, 'USD'));

        $this->upsShippingMethod = new UPSShippingMethod(
            $this->transportProvider,
            $this->channel,
            $this->priceRequestFactory,
            $this->localizationHelper,
            $this->cache
        );
        $prices = $this->upsShippingMethod->calculatePrices($context, $methodOptions, $optionsByTypes);

        static::assertEquals([
            '01' => Price::create(90, 'USD'),
            '02' => Price::create(110, 'USD'),
        ], $prices);
    }
}
