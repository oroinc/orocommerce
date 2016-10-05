<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Method;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;

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
     * @var PriceRequestFactory
     */
    protected $priceRequestFactory;

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

        $this->priceRequestFactory = new PriceRequestFactory($this->registry);
    }

    /**
     * @param int $lineItemCnt
     * @param int $productWeight
     * @param string $unitOfWeight
     * @param array $expectedPackages
     *
     * @dataProvider packagesDataProvider
     */
    public function testCreate($lineItemCnt, $productWeight, $unitOfWeight, $expectedPackages)
    {
        $this->transport->setUnitOfWeight($unitOfWeight);

        $lineItems = [];
        for ($i = 1; $i <= $lineItemCnt; $i++) {
            /** @var Product $product */
            $product = $this->getEntity(Product::class, ['id' => $i]);

            /** @var ShippingLineItem $lineItem */
            $lineItems[] = $this->getEntity(ShippingLineItem::class, [
                'entityIdentifier' => $i,
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
        }

        /** @var ShippingContext $context */
        $context = $this->getEntity(
            ShippingContext::class,
            [
                'lineItems' => $lineItems,
                'billingAddress' => new AddressStub(),
                'shippingAddress' => new AddressStub(),
                'shippingOrigin' => new AddressStub(),
                'paymentMethod' => '',
                'currency' => 'USD',
                'subtotal' => new Price(),
            ]
        );

        /** @var ProductShippingOptions $productShippingOptions */
        $productShippingOptions = $this->getEntity(
            ProductShippingOptions::class,
            [
                'id' => 42,
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

        $repository = $this->getMockBuilder(ObjectRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(self::any())->method('findOneBy')->willReturn($productShippingOptions);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects(self::any())->method('getRepository')->willReturn($repository);

        $this->registry->expects(self::any())->method('getManagerForClass')->willReturn($manager);

        $request = $this->priceRequestFactory->create($this->transport, $context, 'Rate', $this->shippingService);

        $expectedRequest = new PriceRequest();
        $expectedRequest
            ->setSecurity('some user', 'some password', 'some key')
            ->setRequestOption('Rate')
            ->setShipper('some name', 'some number', new AddressStub())
            ->setShipFrom('some name', new AddressStub())
            ->setShipTo(null, new AddressStub())
            ->setPackages($expectedPackages);

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
                'expectedPackages' => [
                    $this->createPackage(14, 14, 14, 60, UPSTransport::UNIT_OF_WEIGHT_LBS)
                ]
            ],
            'TwoPackages-LBS' => [
                'lineItemCnt' => 3,
                'productWeight' => 50,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_LBS,
                'expectedPackages' => [
                    $this->createPackage(14, 14, 14, 100, UPSTransport::UNIT_OF_WEIGHT_LBS),
                    $this->createPackage(7, 7, 7, 50, UPSTransport::UNIT_OF_WEIGHT_LBS),
                ]
            ],
            'OnePackage-KGS' => [
                'lineItemCnt' => 2,
                'productWeight' => 30,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_KGS,
                'expectedPackages' => [
                    $this->createPackage(14, 14, 14, 60, UPSTransport::UNIT_OF_WEIGHT_KGS)
                ]
            ],
            'TwoPackages-KGS' => [
                'lineItemCnt' => 3,
                'productWeight' => 30,
                'unitOfWeight' => UPSTransport::UNIT_OF_WEIGHT_KGS,
                'expectedPackages' => [
                    $this->createPackage(14, 14, 14, 60, UPSTransport::UNIT_OF_WEIGHT_KGS),
                    $this->createPackage(7, 7, 7, 30, UPSTransport::UNIT_OF_WEIGHT_KGS),
                ]
            ],
        ];
    }

    public function testCreatePackages()
    {
        /** @var ProductShippingOptions $productShippingOptions */
        $productShippingOptions = $this->getEntity(
            ProductShippingOptions::class,
            [
                'id' => 42,
                'productUnit' => $this->getEntity(
                    ProductUnit::class,
                    ['code' => 'test1']
                ),
                'dimensions' => Dimensions::create(7, 8, 9, (new LengthUnit())->setCode('inch')),
                'weight' => Weight::create(2, $this->getEntity(
                    WeightUnit::class,
                    ['code' => 'kg']
                ))
            ]
        );

        $repository = $this->getMockBuilder(ObjectRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(self::any())->method('findOneBy')->willReturn($productShippingOptions);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects(self::any())->method('getRepository')->willReturn($repository);

        $this->registry->expects(self::any())->method('getManagerForClass')->willReturn($manager);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 22]);

        /** @var ShippingLineItem $lineItem */
        $lineItem = $this->getEntity(ShippingLineItem::class, ['product' => $product, 'quantity' => 1]);

        $createPackagesReflection = self::getMethod('createPackages');
        $packages = $createPackagesReflection->invokeArgs(
            $this->priceRequestFactory,
            [[$lineItem], 'KG', 70]
        );

        $this->assertCount(1, $packages);

        /** @var Package $package */
        $package = reset($packages);

        $this->assertInstanceOf(Package::class, $package);
        $this->assertEquals('00', $package->getPackagingTypeCode());
        $this->assertEquals(7, $package->getDimensionLength());
        $this->assertEquals(8, $package->getDimensionWidth());
        $this->assertEquals(9, $package->getDimensionHeight());
        $this->assertEquals('KG', $package->getWeightCode());
        $this->assertEquals(2, $package->getWeight());
    }

    public function testGetProductsParamsByUnit()
    {
        /** @var ProductShippingOptions $productShippingOptions */
        $productShippingOptions = $this->getEntity(
            ProductShippingOptions::class,
            [
                'id' => 42,
                'productUnit' => $this->getEntity(
                    ProductUnit::class,
                    ['code' => 'test1']
                ),
                'dimensions' => Dimensions::create(7, 8, 9, $this->getEntity(LengthUnit::class, ['code' => 'inch'])),
                'weight' => Weight::create(2, $this->getEntity(
                    WeightUnit::class,
                    ['code' => 'kg']
                ))
            ]
        );

        $repository = $this->getMockBuilder(ObjectRepository::class)->disableOriginalConstructor()->getMock();
        $repository->expects(self::any())->method('findOneBy')->willReturn($productShippingOptions);

        $manager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();
        $manager->expects(self::any())->method('getRepository')->willReturn($repository);

        $this->registry->expects(self::any())->method('getManagerForClass')->willReturn($manager);

        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 22]);

        /** @var ShippingLineItem $lineItem */
        $lineItem = $this->getEntity(ShippingLineItem::class, ['product' => $product, 'quantity' => 1]);

        $productsParamsByUnitReflection = self::getMethod('getProductsParamsByUnit');
        $productsByUnit = $productsParamsByUnitReflection->invokeArgs(
            $this->priceRequestFactory,
            [[$lineItem]]
        );


        $this->assertCount(1, $productsByUnit);
        $this->assertArrayHasKey('IN', $productsByUnit);
        $this->assertArrayHasKey('KG', $productsByUnit['IN']);
        $this->assertCount(1, $productsByUnit['IN']['KG']);

        $productByUnit = reset($productsByUnit['IN']['KG']);

        $this->assertEquals('inch', $productByUnit['dimensionUnit']);
        $this->assertEquals(9, $productByUnit['dimensionHeight']);
        $this->assertEquals(8, $productByUnit['dimensionWidth']);
        $this->assertEquals(7, $productByUnit['dimensionLength']);
        $this->assertEquals('kg', $productByUnit['weightUnit']);
        $this->assertEquals(2, $productByUnit['weight']);
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
     * @param string $name
     * @return \ReflectionMethod
     */
    protected static function getMethod($name)
    {
        $class = new \ReflectionClass(PriceRequestFactory::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}
