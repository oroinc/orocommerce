<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Provider\UnitsMapper;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var UPSTransport|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $transport;

    /**
     * @var ShippingService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $shippingService;

    /**
     * @var MeasureUnitConversion|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $measureUnitConversion;

    /**
     * @var UnitsMapper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $unitsMapper;

    /**
     * @var PriceRequestFactory
     */
    protected $priceRequestFactory;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $symmetricCrypter;

    protected function setUp(): void
    {
        $this->shippingService = $this->createMock(ShippingService::class);

        $this->transport = $this->getEntity(
            UPSTransport::class,
            [
                'upsApiUser' => 'some user',
                'upsApiPassword' => 'some password',
                'upsApiKey' => 'some key',
                'upsShippingAccountNumber' => 'some number',
                'upsShippingAccountName' => 'some name',
                'upsPickupType' => '01',
                'upsCountry' => new Country('US'),
                'applicableShippingServices' => [new ShippingService()],
            ]
        );

        $this->measureUnitConversion = $this->getMockBuilder(MeasureUnitConversion::class)
            ->disableOriginalConstructor()->getMock();
        $this->measureUnitConversion->expects(static::any())->method('convert')->willReturnCallback(
            function () {
                $args = func_get_args();

                return $args[0];
            }
        );

        $this->unitsMapper = $this->getMockBuilder(UnitsMapper::class)
            ->disableOriginalConstructor()->getMock();
        $this->unitsMapper->expects(static::any())->method('getShippingUnitCode')->willReturn('lbs');

        $this->symmetricCrypter = $this
            ->getMockBuilder(SymmetricCrypterInterface::class)
            ->getMock();

        $this->priceRequestFactory = new PriceRequestFactory(
            $this->measureUnitConversion,
            $this->unitsMapper,
            $this->symmetricCrypter
        );
    }

    /**
     * @param int               $lineItemCnt
     * @param int               $productWeight
     * @param string            $unitOfWeight
     * @param PriceRequest|null $expectedRequest
     *
     * @dataProvider packagesDataProvider
     */
    public function testCreate($lineItemCnt, $productWeight, $unitOfWeight, $expectedRequest)
    {
        $this->symmetricCrypter
            ->expects($this->once())
            ->method('decryptData')
            ->with('some password')
            ->willReturn('some password');

        $this->transport->setUpsUnitOfWeight($unitOfWeight);

        $lineItems = [];
        for ($i = 1; $i <= $lineItemCnt; $i++) {
            /** @var Product $product */
            $product = $this->getEntity(Product::class, ['id' => $i]);

            $lineItems[] = new ShippingLineItem([
                ShippingLineItem::FIELD_PRODUCT => $product,
                ShippingLineItem::FIELD_QUANTITY => 1,
                ShippingLineItem::FIELD_PRODUCT_UNIT => $this->getEntity(
                    ProductUnit::class,
                    ['code' => 'test1']
                ),
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => 'test1',
                ShippingLineItem::FIELD_ENTITY_IDENTIFIER => 1,
                ShippingLineItem::FIELD_WEIGHT => Weight::create($productWeight, $this->getEntity(
                    WeightUnit::class,
                    ['code' => 'lbs']
                )),
            ]);
        }

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($lineItems),
            ShippingContext::FIELD_BILLING_ADDRESS => new AddressStub(),
            ShippingContext::FIELD_SHIPPING_ORIGIN => new AddressStub(),
            ShippingContext::FIELD_SHIPPING_ADDRESS => new AddressStub(),
            ShippingContext::FIELD_PAYMENT_METHOD => '',
            ShippingContext::FIELD_CURRENCY => 'USD',
            ShippingContext::FIELD_SUBTOTAL => new Price(),
        ]);

        $request = $this->priceRequestFactory->create($this->transport, $context, 'Rate', $this->shippingService);

        static::assertEquals($expectedRequest, $request);
    }

    /**
     * @return array
     */
    public function packagesDataProvider()
    {
        return [
            'OnePackage-LBS' => [
                'lineItemCnt' => 2,
                'productWeight' => 30,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_LBS,
                'expectedRequest' => $this->createRequest([
                    $this->createPackage(60, UPSTransport::UNIT_OF_WEIGHT_LBS),
                ]),
            ],
            'TwoPackages-LBS' => [
                'lineItemCnt' => 3,
                'productWeight' => 50,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_LBS,
                'expectedRequest' => $this->createRequest([
                    $this->createPackage(150, UPSTransport::UNIT_OF_WEIGHT_LBS),
                ]),
            ],
            'OnePackage-KGS' => [
                'lineItemCnt' => 2,
                'productWeight' => 30,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_KGS,
                'expectedRequest' => $this->createRequest([
                    $this->createPackage(60, UPSTransport::UNIT_OF_WEIGHT_KGS),
                ]),
            ],
            'TwoPackages-KGS' => [
                'lineItemCnt' => 3,
                'productWeight' => 30,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_KGS,
                'expectedRequest' => $this->createRequest([
                    $this->createPackage(60, UPSTransport::UNIT_OF_WEIGHT_KGS),
                    $this->createPackage(30, UPSTransport::UNIT_OF_WEIGHT_KGS),
                ]),
            ],
            'NoPackages' => [
                'lineItemCnt' => 0,
                'productWeight' => 30,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_KGS,
                'expectedRequest' => null,
            ],
        ];
    }

    public function testCreateWithNullShippingAddress()
    {
        $priceRequest = $this->priceRequestFactory->create($this->transport, new ShippingContext([]), '');

        self::assertNull($priceRequest);
    }

    /**
     * @param int    $weight
     * @param string $unitOfWeight
     *
     * @return Package
     */
    protected function createPackage($weight, $unitOfWeight)
    {
        $expectedPackage = new Package();
        $expectedPackage
            ->setPackagingTypeCode('00')
            ->setWeight((string)$weight)
            ->setWeightCode($unitOfWeight);

        return $expectedPackage;
    }

    /**
     * @param array $expectedPackages
     *
     * @return PriceRequest
     */
    protected function createRequest($expectedPackages)
    {
        $expectedRequest = new PriceRequest();
        $expectedRequest
            ->setSecurity('some user', 'some password', 'some key')
            ->setRequestOption('Rate')
            ->setShipper('some name', 'some number', new AddressStub())
            ->setShipFrom('some name', new AddressStub())
            ->setShipTo(null, new AddressStub())
            ->setPackages($expectedPackages);

        return $expectedRequest;
    }
}
