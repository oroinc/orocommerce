<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Provider\ShippingLineItemsWithShippingOptionsProvider;
use Oro\Bundle\FedexShippingBundle\Transformer\FedexToShippingUnitTransformerInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Provider\MeasureUnitConversion;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingLineItemsWithShippingOptionsProviderTest extends TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $registry;

    /**
     * @var MeasureUnitConversion|\PHPUnit_Framework_MockObject_MockObject
     */
    private $measureUnitConverter;

    /**
     * @var FedexToShippingUnitTransformerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $weightUnitTransformer;

    /**
     * @var FedexToShippingUnitTransformerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dimensionsUnitTransformer;

    /**
     * @var ShippingContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var ShippingLineItemsWithShippingOptionsProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->measureUnitConverter = $this->createMock(MeasureUnitConversion::class);
        $this->weightUnitTransformer = $this->createMock(FedexToShippingUnitTransformerInterface::class);
        $this->dimensionsUnitTransformer = $this->createMock(FedexToShippingUnitTransformerInterface::class);
        $this->context = $this->createMock(ShippingContextInterface::class);

        $this->provider = new ShippingLineItemsWithShippingOptionsProvider(
            $this->registry,
            $this->measureUnitConverter,
            $this->weightUnitTransformer,
            $this->dimensionsUnitTransformer
        );
    }

    public function testGetNoProduct()
    {
        $lineItems = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([])
        ]);

        $this->context
            ->expects(static::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->registry
            ->expects(static::never())
            ->method('getManagerForClass');

        static::assertCount(0, $this->provider->get($this->createSettings(), $this->context));
    }

    public function testGetOptionsWithNoWeight()
    {
        $products = [new Product()];
        $productUnits = [new ProductUnit()];
        $options = [
            $this->createShippingOptions(null, new Dimensions()),
        ];

        $lineItems = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([
                ShippingLineItem::FIELD_PRODUCT => $products[0],
                ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnits[0],
            ])
        ]);

        $this->context
            ->expects(static::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->measureUnitConverter
            ->expects(static::never())
            ->method('convertDimensions');

        $this->setRegistryExpectations($options, $products, $productUnits);

        static::assertCount(0, $this->provider->get($this->createSettings(), $this->context));
    }

    public function testGetOptionsWithNoDimensions()
    {
        $products = [new Product()];
        $productUnits = [new ProductUnit()];
        $options = [
            $this->createShippingOptions(new Weight()),
        ];

        $lineItems = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([
                ShippingLineItem::FIELD_PRODUCT => $products[0],
                ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnits[0],
            ])
        ]);

        $this->context
            ->expects(static::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->measureUnitConverter
            ->expects(static::never())
            ->method('convertDimensions');

        $this->setRegistryExpectations($options, $products, $productUnits);

        static::assertCount(0, $this->provider->get($this->createSettings(), $this->context));
    }

    public function testGetOptionsUnableToConvertDimensionUnit()
    {
        $products = [new Product()];
        $productUnits = [new ProductUnit()];
        $options = [
            $this->createShippingOptions(new Weight(), new Dimensions()),
        ];

        $lineItems = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([
                ShippingLineItem::FIELD_PRODUCT => $products[0],
                ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnits[0],
            ])
        ]);

        $this->context
            ->expects(static::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->measureUnitConverter
            ->expects(static::once())
            ->method('convertDimensions');

        $this->setRegistryExpectations($options, $products, $productUnits);

        static::assertCount(0, $this->provider->get($this->createSettings(), $this->context));
    }

    public function testGetOptionsUnableToConvertWeightUnit()
    {
        $products = [new Product()];
        $productUnits = [new ProductUnit()];
        $options = [
            $this->createShippingOptions(new Weight(), new Dimensions()),
        ];

        $lineItems = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([
                ShippingLineItem::FIELD_PRODUCT => $products[0],
                ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnits[0],
            ])
        ]);

        $this->context
            ->expects(static::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->measureUnitConverter
            ->expects(static::once())
            ->method('convertDimensions')
            ->willReturn(new Dimensions());
        $this->measureUnitConverter
            ->expects(static::once())
            ->method('convertWeight');

        $this->setRegistryExpectations($options, $products, $productUnits);

        static::assertCount(0, $this->provider->get($this->createSettings(), $this->context));
    }

    public function testGetNoOptionForLineItem()
    {
        $products = [
            $this->createProduct(1),
            $this->createProduct(2),
        ];
        $productUnits = [
            (new ProductUnit())->setCode('1'),
            (new ProductUnit())->setCode('2'),
        ];
        $options = [
            $this->createShippingOptions(new Weight(), new Dimensions(), $products[0], $productUnits[0]),
        ];

        $lineItems = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([
                ShippingLineItem::FIELD_PRODUCT => $products[0],
                ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnits[0],
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnits[0]->getCode(),
            ]),
            new ShippingLineItem([
                ShippingLineItem::FIELD_PRODUCT => $products[1],
                ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnits[1],
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnits[1]->getCode(),
            ])
        ]);

        $this->context
            ->expects(static::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->measureUnitConverter
            ->expects(static::once())
            ->method('convertDimensions')
            ->willReturn(new Dimensions());
        $this->measureUnitConverter
            ->expects(static::once())
            ->method('convertWeight')
            ->willReturn(new Weight());

        $this->setRegistryExpectations($options, $products, $productUnits);

        static::assertCount(0, $this->provider->get($this->createSettings(), $this->context));
    }

    public function testGet()
    {
        $dimensions = [
            Dimensions::create(1, 2, 3),
            Dimensions::create(3, 2, 1),
        ];
        $weights = [
            Weight::create(10),
            Weight::create(15),
        ];
        $products = [
            $this->createProduct(1),
            $this->createProduct(2),
        ];
        $productUnits = [
            (new ProductUnit())->setCode('1'),
            (new ProductUnit())->setCode('2'),
        ];
        $options = [
            $this->createShippingOptions($weights[0], $dimensions[0], $products[0], $productUnits[0]),
            $this->createShippingOptions($weights[1], $dimensions[1], $products[1], $productUnits[1]),
        ];
        $quantities = [2, 5];

        $lineItems = new DoctrineShippingLineItemCollection([
            new ShippingLineItem([
                ShippingLineItem::FIELD_PRODUCT => $products[0],
                ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnits[0],
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnits[0]->getCode(),
                ShippingLineItem::FIELD_QUANTITY => $quantities[0],
            ]),
            new ShippingLineItem([
                ShippingLineItem::FIELD_PRODUCT => $products[1],
                ShippingLineItem::FIELD_PRODUCT_UNIT => $productUnits[1],
                ShippingLineItem::FIELD_PRODUCT_UNIT_CODE => $productUnits[1]->getCode(),
                ShippingLineItem::FIELD_QUANTITY => $quantities[1],
            ])
        ]);

        $this->context
            ->expects(static::once())
            ->method('getLineItems')
            ->willReturn($lineItems);

        $this->measureUnitConverter
            ->expects(static::exactly(2))
            ->method('convertDimensions')
            ->willReturnOnConsecutiveCalls($dimensions[0], $dimensions[1]);
        $this->measureUnitConverter
            ->expects(static::exactly(2))
            ->method('convertWeight')
            ->willReturnOnConsecutiveCalls($weights[0], $weights[1]);

        $this->setRegistryExpectations($options, $products, $productUnits);

        static::assertEquals(
            [
                new ShippingLineItem([
                    ShippingLineItem::FIELD_WEIGHT => $weights[0],
                    ShippingLineItem::FIELD_DIMENSIONS => $dimensions[0],
                    ShippingLineItem::FIELD_QUANTITY => $quantities[0],
                ]),
                new ShippingLineItem([
                    ShippingLineItem::FIELD_WEIGHT => $weights[1],
                    ShippingLineItem::FIELD_DIMENSIONS => $dimensions[1],
                    ShippingLineItem::FIELD_QUANTITY => $quantities[1],
                ]),
            ],
            $this->provider->get($this->createSettings(), $this->context)
        );
    }

    /**
     * @param int $id
     *
     * @return Product
     */
    private function createProduct(int $id): Product
    {
        $product = $this->createMock(Product::class);
        $product
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $product;
    }

    /**
     * @param Weight|null      $weight
     * @param Dimensions|null  $dimensions
     * @param Product|null     $product
     * @param ProductUnit|null $unit
     *
     * @return ProductShippingOptions
     */
    private function createShippingOptions(
        Weight $weight = null,
        Dimensions $dimensions = null,
        Product $product = null,
        ProductUnit $unit = null
    ): ProductShippingOptions {
        $options = new ProductShippingOptions();

        $options
            ->setDimensions($dimensions)
            ->setWeight($weight)
            ->setProduct($product)
            ->setProductUnit($unit);

        return $options;
    }

    /**
     * @param ProductShippingOptions[] $options
     * @param Product[]                $products
     * @param ProductUnit[]            $productUnits
     */
    private function setRegistryExpectations(array $options, array $products, array $productUnits)
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->expects(static::once())
            ->method('findBy')
            ->with([
                'product' => $products,
                'productUnit' => $productUnits,
            ])
            ->willReturn($options);

        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects(static::once())
            ->method('getRepository')
            ->willReturn($repository);

        $this->registry
            ->expects(static::once())
            ->method('getManagerForClass')
            ->willReturn($manager);
    }

    /**
     * @return FedexIntegrationSettings
     */
    private function createSettings(): FedexIntegrationSettings
    {
        $settings = new FedexIntegrationSettings();

        $settings->setUnitOfWeight(FedexIntegrationSettings::UNIT_OF_WEIGHT_KG);

        return $settings;
    }
}
