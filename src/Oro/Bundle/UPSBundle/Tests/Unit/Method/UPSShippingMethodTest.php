<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
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

        $this->upsShippingMethod =
            new UPSShippingMethod(
                $this->transportProvider,
                $this->channel,
                $this->priceRequestFactory,
                $this->localizationHelper
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
        $priceRequest->expects(self::once())->method('getPackages')->willReturn([new Package(), new Package()]);

        $this->priceRequestFactory->expects(self::once())->method('create')->willReturn($priceRequest);

        $priceResponse = $this->getMockBuilder(PriceResponse::class)->disableOriginalConstructor()->getMock();
        $priceResponse->expects(self::once())
            ->method('getPricesByServices')
            ->willReturn(['01' => Price::create(50, 'USD')]);

        $this->transportProvider->expects(self::once())->method('getPrices')->willReturn($priceResponse);


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
}
