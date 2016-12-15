<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Bundle\UPSBundle\Provider\UnitsMapper;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UPSTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var ShippingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingService;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var MeasureUnitConversion|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $measureUnitConversion;

    /**
     * @var UnitsMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $unitsMapper;

    /**
     * @var PriceRequestFactory
     */
    protected $priceRequestFactory;

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $symmetricCrypter;

    protected function setUp()
    {
        /** @var ManagerRegistry | \PHPUnit_Framework_MockObject_MockObject $doctrine */
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()->getMock();

        $this->shippingService = $this->getMock(ShippingService::class);

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
                'country' => new Country('US'),
                'applicableShippingServices' => [new ShippingService()]
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
            $this->registry,
            $this->measureUnitConversion,
            $this->unitsMapper,
            $this->symmetricCrypter
        );
    }

    /**
     * @param int $lineItemCnt
     * @param int $productWeight
     * @param string $unitOfWeight
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

        $this->transport->setUnitOfWeight($unitOfWeight);

        $lineItems = [];
        $allProductsShippingOptions = [];
        for ($i = 1; $i <= $lineItemCnt; $i++) {
            /** @var Product $product */
            $product = $this->getEntity(Product::class, ['id' => $i]);

            /** @var ShippingLineItem $lineItem */
            $lineItems[] = $this->getEntity(ShippingLineItem::class, [
                'product' => $product,
                'quantity' => 1,
                'productUnit' => $this->getEntity(
                    ProductUnit::class,
                    ['code' => 'test1']
                ),
                'dimensions' => Dimensions::create(7, 7, 7, (new LengthUnit())->setCode('inch')),
                'weight' => Weight::create($productWeight, $this->getEntity(
                    WeightUnit::class,
                    ['code' => 'lbs']
                ))
            ]);

            /** @var ProductShippingOptions $productShippingOptions */
            $allProductsShippingOptions[] = $this->getEntity(
                ProductShippingOptions::class,
                [
                    'id' => 42,
                    'product' => $product,
                    'productUnit' => $this->getEntity(
                        ProductUnit::class,
                        ['code' => 'test1']
                    ),
                    'dimensions' => Dimensions::create(7, 7, 7, (new LengthUnit())->setCode('inch')),
                    'weight' => Weight::create($productWeight, $this->getEntity(
                        WeightUnit::class,
                        ['code' => 'kg']
                    ))
                ]
            );
        }

        $context = new ShippingContext([
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection($lineItems),
            ShippingContext::FIELD_BILLING_ADDRESS => new AddressStub(),
            ShippingContext::FIELD_SHIPPING_ORIGIN => new AddressStub(),
            ShippingContext::FIELD_SHIPPING_ADDRESS => new AddressStub(),
            ShippingContext::FIELD_PAYMENT_METHOD => '',
            ShippingContext::FIELD_CURRENCY => 'USD',
            ShippingContext::FIELD_SUBTOTAL => new Price()
        ]);

        $repository = $this->getMockBuilder(ObjectRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(self::any())->method('findBy')->willReturn($allProductsShippingOptions);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects(self::any())->method('getRepository')->willReturn($repository);

        $this->registry->expects(self::any())->method('getManagerForClass')->willReturn($manager);

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
                    $this->createPackage(14, 14, 14, 60, UPSTransport::UNIT_OF_WEIGHT_LBS)
                ])
            ],
            'TwoPackages-LBS' => [
                'lineItemCnt' => 3,
                'productWeight' => 50,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_LBS,
                'expectedRequest' => $this->createRequest([
                    $this->createPackage(21, 21, 21, 150, UPSTransport::UNIT_OF_WEIGHT_LBS),
                ])
            ],
            'OnePackage-KGS' => [
                'lineItemCnt' => 2,
                'productWeight' => 30,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_KGS,
                'expectedRequest' => $this->createRequest([
                    $this->createPackage(14, 14, 14, 60, UPSTransport::UNIT_OF_WEIGHT_KGS)
                ])
            ],
            'TwoPackages-KGS' => [
                'lineItemCnt' => 3,
                'productWeight' => 30,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_KGS,
                'expectedRequest' => $this->createRequest([
                    $this->createPackage(14, 14, 14, 60, UPSTransport::UNIT_OF_WEIGHT_KGS),
                    $this->createPackage(7, 7, 7, 30, UPSTransport::UNIT_OF_WEIGHT_KGS),
                ])
            ],
            'NoPackages' => [
                'lineItemCnt' => 0,
                'productWeight' => 30,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_KGS,
                'expectedRequest' => null
            ],
        ];
    }

    /**
     * @param int $length
     * @param int $width
     * @param int $height
     * @param int $weight
     * @param string $unitOfWeight
     * @return Package
     */
    protected function createPackage($length, $width, $height, $weight, $unitOfWeight)
    {
        $expectedPackage = new Package();
        $expectedPackage
            ->setPackagingTypeCode('00')
            ->setDimensionLength((string)$length)
            ->setDimensionWidth((string)$width)
            ->setDimensionHeight((string)$height)
            ->setDimensionCode('IN')
            ->setWeight((string)$weight)
            ->setWeightCode($unitOfWeight);

        return $expectedPackage;
    }

    /**
     * @param array $expectedPackages
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
