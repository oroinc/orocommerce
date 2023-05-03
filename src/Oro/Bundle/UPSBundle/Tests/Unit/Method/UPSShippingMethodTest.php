<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCacheKey;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Form\Type\UPSShippingMethodOptionsType;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Model\PriceResponse;
use Oro\Bundle\UPSBundle\Provider\UPSTransport as UPSTransportProvider;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UPSShippingMethodTest extends \PHPUnit\Framework\TestCase
{
    private const IDENTIFIER = 'ups_1';
    private const LABEL = 'ups_label';
    private const TYPE_IDENTIFIER = '59';
    private const ICON = 'bundles/icon-uri.png';

    /** @var UPSTransportProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $transportProvider;

    /** @var UPSTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var PriceRequestFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $priceRequestFactory;

    /** @var ShippingPriceCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var UPSShippingMethod */
    private $upsShippingMethod;

    protected function setUp(): void
    {
        $this->transportProvider = $this->createMock(UPSTransportProvider::class);
        $this->transport = $this->createMock(UPSTransport::class);
        $this->priceRequestFactory = $this->createMock(PriceRequestFactory::class);
        $this->cache = $this->createMock(ShippingPriceCache::class);

        $shippingService = new ShippingService();
        ReflectionUtil::setId($shippingService, 1);
        $shippingService->setCode('ups_identifier');
        $shippingService->setDescription('ups_label');
        $shippingService->setCountry(new Country('US'));

        $this->transport->expects(self::any())
            ->method('getApplicableShippingServices')
            ->willReturn([$shippingService]);

        $type = $this->createMock(UPSShippingMethodType::class);
        $type->expects(self::any())
            ->method('getIdentifier')
            ->willReturn(self::TYPE_IDENTIFIER);

        $this->upsShippingMethod = new UPSShippingMethod(
            self::IDENTIFIER,
            self::LABEL,
            self::ICON,
            [$type],
            $this->transport,
            $this->transportProvider,
            $this->priceRequestFactory,
            $this->cache,
            true
        );
    }

    public function testIsGrouped()
    {
        self::assertTrue($this->upsShippingMethod->isGrouped());
    }

    public function testIsEnabled()
    {
        self::assertTrue($this->upsShippingMethod->isEnabled());
    }

    public function testGetIdentifier()
    {
        self::assertEquals(self::IDENTIFIER, $this->upsShippingMethod->getIdentifier());
    }

    public function testGetLabel()
    {
        self::assertEquals(self::LABEL, $this->upsShippingMethod->getLabel());
    }

    public function testGetTypes()
    {
        $types = $this->upsShippingMethod->getTypes();

        self::assertCount(1, $types);
        self::assertEquals(self::TYPE_IDENTIFIER, $types[0]->getIdentifier());
    }

    public function testGetType()
    {
        $identifier = self::TYPE_IDENTIFIER;
        $type = $this->upsShippingMethod->getType($identifier);

        self::assertInstanceOf(UPSShippingMethodType::class, $type);
        self::assertEquals(self::TYPE_IDENTIFIER, $type->getIdentifier());
    }

    public function testGetOptionsConfigurationFormType()
    {
        $type = $this->upsShippingMethod->getOptionsConfigurationFormType();

        self::assertEquals(UPSShippingMethodOptionsType::class, $type);
    }

    public function testGetSortOrder()
    {
        self::assertEquals('20', $this->upsShippingMethod->getSortOrder());
    }

    /**
     * @dataProvider calculatePricesDataProvider
     */
    public function testCalculatePrices(int $methodSurcharge, int $typeSurcharge, int $expectedPrice)
    {
        $context = $this->createMock(ShippingContextInterface::class);

        $methodOptions = ['surcharge' => $methodSurcharge];
        $optionsByTypes = ['01' => ['surcharge' => $typeSurcharge]];

        $priceRequest = $this->createMock(PriceRequest::class);

        $this->priceRequestFactory->expects(self::once())
            ->method('create')
            ->willReturn($priceRequest);

        $this->transportProvider->expects(self::never())
            ->method('getPriceResponse');

        $cacheKey = (new ShippingPriceCacheKey())
            ->setTransport($this->transport)
            ->setPriceRequest($priceRequest)
            ->setMethodId($this->upsShippingMethod->getIdentifier())
            ->setTypeId(null);

        $this->cache->expects(self::once())
            ->method('createKey')
            ->with($this->transport, $priceRequest, $this->upsShippingMethod->getIdentifier(), null)
            ->willReturn($cacheKey);

        $this->cache->expects(self::once())
            ->method('containsPrice')
            ->with($cacheKey)
            ->willReturn(true);

        $this->cache->expects(self::once())
            ->method('fetchPrice')
            ->with($cacheKey)
            ->willReturn(Price::create(50, 'USD'));

        $prices = $this->upsShippingMethod->calculatePrices($context, $methodOptions, $optionsByTypes);

        self::assertCount(1, $prices);
        self::assertArrayHasKey('01', $prices);
        self::assertEquals(Price::create($expectedPrice, 'USD'), $prices['01']);
    }

    public function calculatePricesDataProvider(): array
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
     * @dataProvider trackingDataProvider
     */
    public function testGetTrackingLink(string $number, ?string $resultURL)
    {
        self::assertSame($resultURL, $this->upsShippingMethod->getTrackingLink($number));
    }

    public function trackingDataProvider(): array
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
                'resultURL' => 'https://www.ups.com/WebTracking/processInputRequest'
                    . '?TypeOfInquiryNumber=T&InquiryNumber1=1Z111E111111111111',
            ],
        ];
    }

    public function testCalculatePricesWithoutCache()
    {
        $context = $this->createMock(ShippingContextInterface::class);

        $methodOptions = ['surcharge' => 10];

        $priceRequest = $this->createMock(PriceRequest::class);

        $cacheKey = (new ShippingPriceCacheKey())
            ->setTransport($this->transport)
            ->setPriceRequest($priceRequest)
            ->setMethodId($this->upsShippingMethod->getIdentifier());

        $this->priceRequestFactory->expects(self::once())
            ->method('create')
            ->willReturn($priceRequest);

        $this->cache->expects(self::once())
            ->method('createKey')
            ->with($this->transport, $priceRequest, $this->upsShippingMethod->getIdentifier(), null)
            ->willReturn($cacheKey);
        $this->cache->expects(self::exactly(4))
            ->method('containsPrice')
            ->withConsecutive(
                [$cacheKey->setTypeId('01')],
                [$cacheKey->setTypeId('02')],
                [$cacheKey->setTypeId('03')],
                [$cacheKey->setTypeId('04')]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                true,
                false,
                false
            );
        $this->cache->expects(self::exactly(2))
            ->method('fetchPrice')
            ->with($cacheKey)
            ->willReturnOnConsecutiveCalls(
                Price::create(60, 'USD'),
                Price::create(70, 'USD')
            );

        $priceResponse = $this->createMock(PriceResponse::class);
        $priceResponse->expects(self::exactly(2))
            ->method('getPriceByService')
            ->withConsecutive(
                ['03'],
                ['04']
            )
            ->willReturnOnConsecutiveCalls(
                Price::create(80, 'USD'),
                Price::create(90, 'USD')
            );

        $this->transportProvider->expects(self::once())
            ->method('getPriceResponse')
            ->willReturn($priceResponse);

        $this->cache->expects(self::exactly(2))
            ->method('savePrice')
            ->with($cacheKey->setTypeId('03'))
            ->willReturnOnConsecutiveCalls(true, true);

        $optionsByTypes = [
            '01' => ['surcharge' => 20],
            '02' => ['surcharge' => 30],
            '03' => ['surcharge' => 40],
            '04' => ['surcharge' => 50],
        ];

        $prices = $this->upsShippingMethod->calculatePrices($context, $methodOptions, $optionsByTypes);

        self::assertEquals([
            '01' => Price::create(90, 'USD'),
            '02' => Price::create(110, 'USD'),
            '03' => Price::create(130, 'USD'),
            '04' => Price::create(150, 'USD'),
        ], $prices);
    }

    public function testCalculatePricesOneWithoutCache()
    {
        $context = $this->createMock(ShippingContextInterface::class);

        $methodOptions = ['surcharge' => 10];

        $priceRequest = $this->createMock(PriceRequest::class);

        $cacheKey = (new ShippingPriceCacheKey())
            ->setTransport($this->transport)
            ->setPriceRequest($priceRequest)
            ->setMethodId($this->upsShippingMethod->getIdentifier());

        $this->priceRequestFactory->expects(self::once())
            ->method('create')
            ->willReturn($priceRequest);

        $this->cache->expects(self::once())
            ->method('createKey')
            ->with($this->transport, $priceRequest, $this->upsShippingMethod->getIdentifier(), null)
            ->willReturn($cacheKey);
        $this->cache->expects(self::exactly(2))
            ->method('containsPrice')
            ->withConsecutive([$cacheKey->setTypeId('01')], [$cacheKey->setTypeId(self::TYPE_IDENTIFIER)])
            ->willReturnOnConsecutiveCalls(true, false);
        $this->cache->expects(self::once())
            ->method('fetchPrice')
            ->with($cacheKey)
            ->willReturn(Price::create(60, 'USD'));

        $priceResponse = $this->createMock(PriceResponse::class);
        $priceResponse->expects(self::once())
            ->method('getPriceByService')
            ->with(self::TYPE_IDENTIFIER)
            ->willReturn(Price::create(70, 'USD'));

        $this->transport = $this->createMock(UPSTransport::class);
        $service = (new ShippingService())
            ->setCode(self::TYPE_IDENTIFIER)
            ->setDescription('Air');

        $priceRequest->expects(self::once())
            ->method('setServiceCode')
            ->with(self::TYPE_IDENTIFIER)
            ->willReturn($priceRequest);
        $priceRequest->expects(self::once())
            ->method('setServiceDescription')
            ->with('Air')
            ->willReturn($priceRequest);

        $this->transportProvider->expects(self::once())
            ->method('getPriceResponse')
            ->willReturn($priceResponse);

        $this->cache->expects(self::once())
            ->method('savePrice')
            ->with($cacheKey->setTypeId(self::TYPE_IDENTIFIER), Price::create(70, 'USD'))
            ->willReturn(true);

        $type = $this->createMock(UPSShippingMethodType::class);
        $type->expects(self::any())
            ->method('getIdentifier')
            ->willReturn(self::TYPE_IDENTIFIER);
        $type->expects(self::any())
            ->method('getShippingService')
            ->willReturn($service);

        $this->upsShippingMethod = new UPSShippingMethod(
            self::IDENTIFIER,
            self::LABEL,
            self::ICON,
            [$type],
            $this->transport,
            $this->transportProvider,
            $this->priceRequestFactory,
            $this->cache,
            true
        );

        $optionsByTypes = [
            '01' => ['surcharge' => 20],
            self::TYPE_IDENTIFIER => ['surcharge' => 30],
        ];

        $prices = $this->upsShippingMethod->calculatePrices($context, $methodOptions, $optionsByTypes);

        self::assertEquals([
            '01' => Price::create(90, 'USD'),
            self::TYPE_IDENTIFIER => Price::create(110, 'USD'),
        ], $prices);
    }

    public function testGetIcon()
    {
        self::assertSame(self::ICON, $this->upsShippingMethod->getIcon());
    }
}
