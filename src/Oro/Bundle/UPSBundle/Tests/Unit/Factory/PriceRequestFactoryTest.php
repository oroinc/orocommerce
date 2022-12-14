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

    /** @var UPSTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var ShippingService|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingService;

    /** @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $symmetricCrypter;

    /** @var PriceRequestFactory */
    private $priceRequestFactory;

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

        $measureUnitConversion = $this->createMock(MeasureUnitConversion::class);
        $measureUnitConversion->expects(self::any())
            ->method('convert')
            ->willReturnCallback(function () {
                $args = func_get_args();

                return $args[0];
            });

        $unitsMapper = $this->createMock(UnitsMapper::class);
        $unitsMapper->expects(self::any())
            ->method('getShippingUnitCode')
            ->willReturn('lbs');

        $this->symmetricCrypter = $this->createMock(SymmetricCrypterInterface::class);

        $this->priceRequestFactory = new PriceRequestFactory(
            $measureUnitConversion,
            $unitsMapper,
            $this->symmetricCrypter
        );
    }

    /**
     * @dataProvider packagesDataProvider
     */
    public function testCreate(
        int $lineItemCnt,
        int $productWeight,
        string $unitOfWeight,
        ?PriceRequest $expectedRequest
    ) {
        $this->symmetricCrypter->expects(self::once())
            ->method('decryptData')
            ->with('some password')
            ->willReturn('some password');

        $this->transport->setUpsUnitOfWeight($unitOfWeight);

        $lineItems = [];
        for ($i = 1; $i <= $lineItemCnt; $i++) {
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

        self::assertEquals($expectedRequest, $request);
    }

    public function packagesDataProvider(): array
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

    private function createPackage(int $weight, string $unitOfWeight): Package
    {
        $expectedPackage = new Package();
        $expectedPackage
            ->setPackagingTypeCode('00')
            ->setWeight((string)$weight)
            ->setWeightCode($unitOfWeight);

        return $expectedPackage;
    }

    private function createRequest(array $expectedPackages): PriceRequest
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
