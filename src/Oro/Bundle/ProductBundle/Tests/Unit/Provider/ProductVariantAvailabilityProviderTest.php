<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\BooleanVariantFieldValueHandler;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\EnumVariantFieldValueHandler;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductProxyStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ProductVariantAvailabilityProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ProductVariantAvailabilityProvider */
    protected $availabilityProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $productRepository;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $qb;

    /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject */
    protected $query;

    /** @var CustomFieldProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $customFieldProvider;

    /** @var EnumVariantFieldValueHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $enumHandler;

    /** @var BooleanVariantFieldValueHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $boolHandler;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $variantFieldValueHandlerRegistry = new ProductVariantFieldValueHandlerRegistry();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->boolHandler = new BooleanVariantFieldValueHandler($this->translator);

        $this->productRepository = $this->createMock(ProductRepository::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(Product::class)
            ->willReturn($this->productRepository);

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(
                function ($variantValue) {
                    return $variantValue->getId();
                }
            );

        $this->customFieldProvider = $this->getMockBuilder(CustomFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EnumValueProvider|\PHPUnit\Framework\MockObject\MockObject $enumValueProvider */
        $enumValueProvider = $this->getMockBuilder(EnumValueProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger = $this->createMock(LoggerInterface::class);
        $configManager = $this->createMock(ConfigManager::class);

        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localeSettings = $this->createMock(LocaleSettings::class);

        $this->enumHandler = $this->getMockBuilder(EnumVariantFieldValueHandler::class)
            ->setConstructorArgs([
                $this->doctrineHelper,
                $enumValueProvider,
                $logger,
                $configManager,
                $localizationHelper,
                $localeSettings
            ])
            ->setMethods(['getPossibleValues'])
            ->getMock();

        $variantFieldValueHandlerRegistry->addHandler($this->boolHandler);
        $variantFieldValueHandlerRegistry->addHandler($this->enumHandler);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->qb = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->qb
            ->expects($this->any())
            ->method('getQuery')
            ->willReturn($this->query);

        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->availabilityProvider = new ProductVariantAvailabilityProvider(
            $this->doctrineHelper,
            $this->customFieldProvider,
            $propertyAccessor,
            $this->dispatcher,
            $variantFieldValueHandlerRegistry,
            $this->aclHelper
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset(
            $this->availabilityProvider,
            $this->productRepository,
            $this->qb,
            $this->query,
            $this->customFieldProvider,
            $this->enumHandler,
            $this->dispatcher
        );
    }

    /**
     * @return ProductStub
     */
    protected function getConfigurableProduct()
    {
        $configurableProduct = new ProductStub();
        $configurableProduct->setType(Product::TYPE_CONFIGURABLE);

        return $configurableProduct;
    }

    /**
     * @return ProductStub
     */
    protected function getSimpleProduct()
    {
        $simpleProduct = new ProductStub();
        $simpleProduct->setType(Product::TYPE_SIMPLE);

        return $simpleProduct;
    }

    protected function setUpRepositoryResult(Product $configurableProduct, array $variantParameters, array $result)
    {
        $this->productRepository
            ->expects($this->once())
            ->method('getSimpleProductsByVariantFieldsQueryBuilder')
            ->with($configurableProduct, $variantParameters)
            ->willReturn($this->qb);

        $this->query
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($result);
    }

    public function testGetSimpleProductsByVariantFields()
    {
        $configurableProduct = $this->getConfigurableProduct();
        $variantParameters = [
            'size' => 's',
            'color' => 'red',
            'slim_fit' => true
        ];

        $expected = [
            new Product(),
            new Product(),
        ];

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(RestrictProductVariantEvent::class), RestrictProductVariantEvent::NAME);

        $this->setUpRepositoryResult($configurableProduct, $variantParameters, $expected);

        $this->assertSame(
            $expected,
            $this->availabilityProvider->getSimpleProductsByVariantFields($configurableProduct, $variantParameters)
        );

        // Check that second call does not lead to second request and result is returned from the cache
        $this->assertSame(
            $expected,
            $this->availabilityProvider->getSimpleProductsByVariantFields($configurableProduct, $variantParameters)
        );
    }

    public function testGetSimpleProductByVariantFields()
    {
        $configurableProduct = $this->getConfigurableProduct();

        $product1 = $this->getSimpleProduct();
        $products = [
            $product1,
        ];

        $this->setUpRepositoryResult($configurableProduct, [], $products);

        $this->assertSame(
            $product1,
            $this->availabilityProvider->getSimpleProductByVariantFields($configurableProduct)
        );
    }

    public function testGetSimpleProductByVariantFieldsSeveralProducts()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Variant values provided don't match exactly one simple product");

        $configurableProduct = $this->getConfigurableProduct();

        $products = [
            $this->getSimpleProduct(),
            $this->getSimpleProduct(),
        ];

        $this->setUpRepositoryResult($configurableProduct, [], $products);

        $this->availabilityProvider->getSimpleProductByVariantFields($configurableProduct);
    }

    public function testGetSimpleProductByVariantFieldsNoProducts()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Variant values provided don't match exactly one simple product");

        $configurableProduct = $this->getConfigurableProduct();

        $products = [];

        $this->setUpRepositoryResult($configurableProduct, [], $products);

        $this->availabilityProvider->getSimpleProductByVariantFields($configurableProduct);
    }

    public function testGetSimpleProductByVariantFieldsWrongProductType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Product with type "configurable" expected, "simple" given');

        $configurableProduct = $this->getSimpleProduct();

        $this->productRepository
            ->expects($this->never())
            ->method('getSimpleProductsByVariantFieldsQueryBuilder');

        $this->availabilityProvider->getSimpleProductByVariantFields($configurableProduct);
    }

    public function testGetVariantFieldsValuesForVariant()
    {
        $configurableProduct = $this->getConfigurableProduct();
        $configurableProduct->setVariantFields(['color', 'new']);

        $variantsData = [
            'color' => [
                'type' => 'enum',
                'values' => [
                    'red' => 'Red',
                    'green' => 'Green',
                    'blue' => 'Blue',
                ]
            ],
            'new' => [
                'type' => 'boolean',
            ]
        ];

        $expectedProductSku = 'product1';
        $productData = [
            $expectedProductSku => [
                'color' => 'red',
                'new' => true
            ]
        ];

        $this->customFieldProvider->expects($this->any())
            ->method('getEntityCustomFields')
            ->with(Product::class)
            ->willReturn($variantsData);

        $simpleProducts = $this->getSimpleProductsWithVariants($variantsData, $productData);
        $simpleProduct = reset($simpleProducts);

        $actualFields = $this->availabilityProvider
            ->getVariantFieldsValuesForVariant($configurableProduct, $simpleProduct);

        $this->assertEquals($productData[$expectedProductSku], $actualFields);
    }

    /**
     * @param array $variantsData
     * @param array $productData
     * @param array $variantParameters
     * @param array $variantFields
     * @param array $expected
     * @dataProvider variantFieldsAvailabilityProvider
     */
    public function testGetVariantFieldsAvailability(
        $variantsData,
        $productData,
        $variantParameters,
        $variantFields,
        $expected
    ) {
        $configurableProduct = $this->getConfigurableProduct();
        $configurableProduct->setVariantFields($variantFields);

        $this->configureMocks($variantsData, $productData);

        $this->assertEquals(
            $expected,
            $this->availabilityProvider->getVariantFieldsAvailability($configurableProduct, $variantParameters)
        );
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function variantFieldsAvailabilityProvider()
    {
        return [
            'variant 1' => [
                'variantsData' => [
                    'color' => [
                        'type' => 'enum',
                        'values' => [
                            'Red' => 'red',
                            'Green' => 'green',
                            'Blue' => 'blue',
                        ]
                    ],
                    'size' => [
                        'type' => 'enum',
                        'values' => [
                            'S' => 's',
                            'M' => 'm',
                            'L' => 'l',
                            'XL' => 'xl',
                        ]
                    ],
                    'slim_fit' => [
                        'type' => 'boolean',
                        'values' => [0 => false, 1 => true]
                    ],
                    'extended_field' => [
                        'type' => 'string',
                    ],
                ],
                'productData' => [
                    'product1' => [
                        'color' => 'Red',
                        'size' => 'S',
                        'slim_fit' => true,
                    ],
                    'product2' => [
                        'color' => 'Green',
                        'size' => 'M',
                        'slim_fit' => false,
                    ],
                ],
                'variantParameters' => [
                    'size' => 'S',
                    'color' => 'Red',
                    'slim_fit' => true
                ],
                'variantFields' => [
                    'color',
                    'size',
                    'slim_fit',
                ],
                'expected' => [
                    'size' => [
                        'S' => true,
                        'M' => false,
                        'L' => false,
                        'XL' => false,
                    ],
                    'color' => [
                        'Red' => true,
                        'Green' => false,
                        'Blue' => false,
                    ],
                    'slim_fit' => [
                        0 => false,
                        1 => true
                    ]
                ],
            ],
            'variant 2' => [
                'variantsData' => [
                    'color' => [
                        'type' => 'enum',
                        'values' => [
                            'Red' => 'red',
                            'Green' => 'green',
                            'Blue' => 'blue',
                        ]
                    ],
                    'extended_field' => [
                        'type' => 'string',
                    ],
                ],
                'productData' => [
                    'product1' => [
                        'color' => 'Red'
                    ],
                    'product2' => [
                        'color' => 'Green'
                    ],
                    'product3' => [
                        'color' => null
                    ],
                ],
                'variantParameters' => [
                    'color' => 'Green',
                ],
                'variantFields' => [
                    'color',
                ],
                'expected' => [
                    'color' => [
                        'Red' => false,
                        'Green' => true,
                        'Blue' => false,
                    ]
                ],
            ],
        ];
    }

    public function testGetSimpleProductsGroupedByConfigurableNoConfigurable()
    {
        $products = [
            $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE])
        ];

        $this->assertSame([], $this->availabilityProvider->getSimpleProductsGroupedByConfigurable($products));
    }

    public function testGetSimpleProductsGroupedByConfigurableNoSimple()
    {
        $products = [
            $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_CONFIGURABLE])
        ];

        $simpleProductResult = [];
        $this->assertGetSimpleProductsCall($simpleProductResult);

        $this->assertSame([], $this->availabilityProvider->getSimpleProductsGroupedByConfigurable($products));
    }

    public function testGetSimpleProductsGroupedByConfigurable()
    {
        $products = [
            $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_CONFIGURABLE]),
            $this->getEntity(Product::class, ['id' => 2, 'type' => Product::TYPE_CONFIGURABLE]),
            $this->getEntity(Product::class, ['id' => 3, 'type' => Product::TYPE_CONFIGURABLE]),
        ];

        $simpleProductResult = [
            ['id' => 11],
            ['id' => 12],
            ['id' => 13]
        ];
        $this->assertGetSimpleProductsCall($simpleProductResult);

        $variantsMapping = [
            11 => [1],
            12 => [2],
            13 => [1,2]
        ];
        $this->productRepository->expects($this->once())
            ->method('getVariantsMapping')
            ->with($products)
            ->willReturn($variantsMapping);

        $expected = [
            1 => [
                $this->getEntity(Product::class, ['id' => 11]),
                $this->getEntity(Product::class, ['id' => 13])
            ],
            2 => [
                $this->getEntity(Product::class, ['id' => 12]),
                $this->getEntity(Product::class, ['id' => 13])
            ]
        ];
        $this->assertEquals($expected, $this->availabilityProvider->getSimpleProductsGroupedByConfigurable($products));
    }

    public function testGetSimpleProductsByConfigurable()
    {
        $simpleProductResult = [
            ['id' => 1],
            ['id' => 2]
        ];

        $this->assertGetSimpleProductsCall($simpleProductResult);

        $products = [
            $this->getEntity(Product::class, ['id' => 11, 'type' => Product::TYPE_CONFIGURABLE]),
            $this->getEntity(Product::class, ['id' => 12, 'type' => Product::TYPE_CONFIGURABLE])
        ];

        $expected = [
            $this->getEntity(Product::class, ['id' => 1, 'type' => Product::TYPE_SIMPLE]),
            $this->getEntity(Product::class, ['id' => 2, 'type' => Product::TYPE_SIMPLE])
        ];

        $this->assertEquals(
            $expected,
            $this->availabilityProvider->getSimpleProductsByConfigurable($products)
        );
    }

    public function testFilterConfigurableProductsAllInitialized()
    {
        $loadedConfigurableProduct = $this->getEntity(
            Product::class,
            ['id' => 1, 'type' => Product::TYPE_CONFIGURABLE]
        );
        $loadedConfigurableProxy = $this->getEntity(
            ProductProxyStub::class,
            ['id' => 2, 'type' => Product::TYPE_CONFIGURABLE, 'initialized' => true]
        );
        $simpleProduct = $this->getEntity(
            Product::class,
            ['id' => 11, 'type' => Product::TYPE_SIMPLE]
        );

        $this->productRepository->expects($this->never())
            ->method($this->anything());

        $products = [
            $loadedConfigurableProduct,
            $loadedConfigurableProxy,
            $simpleProduct
        ];

        $expected = [
            $loadedConfigurableProduct,
            $loadedConfigurableProxy
        ];

        $this->assertEquals(
            $expected,
            $this->availabilityProvider->filterConfigurableProducts($products)
        );
    }

    public function testFilterConfigurableProductsNotAllInitialized()
    {
        $loadedConfigurableProduct = $this->getEntity(
            Product::class,
            ['id' => 1, 'type' => Product::TYPE_CONFIGURABLE]
        );
        $loadedConfigurableProxy = $this->getEntity(
            ProductProxyStub::class,
            ['id' => 2, 'type' => Product::TYPE_CONFIGURABLE, 'initialized' => true]
        );
        $loadedSimpleProxy = $this->getEntity(
            ProductProxyStub::class,
            ['id' => 3, 'type' => Product::TYPE_SIMPLE, 'initialized' => true]
        );
        $notLoadedProxy1 = $this->getEntity(
            ProductProxyStub::class,
            ['id' => 4, 'initialized' => false]
        );
        $notLoadedProxy2 = $this->getEntity(
            ProductProxyStub::class,
            ['id' => 5, 'initialized' => false]
        );
        $simpleProduct = $this->getEntity(
            Product::class,
            ['id' => 11, 'type' => Product::TYPE_SIMPLE]
        );

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn([['id' => 5]]);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $this->productRepository->expects($this->once())
            ->method('getConfigurableProductIdsQueryBuilder')
            ->with([$notLoadedProxy1, $notLoadedProxy2])
            ->willReturn($queryBuilder);

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $products = [
            $loadedConfigurableProduct,
            $loadedConfigurableProxy,
            $loadedSimpleProxy,
            $notLoadedProxy1,
            $notLoadedProxy2,
            $simpleProduct
        ];

        $expected = [
            $loadedConfigurableProduct,
            $loadedConfigurableProxy,
            $notLoadedProxy2
        ];

        $this->assertEquals(
            $expected,
            $this->availabilityProvider->filterConfigurableProducts($products)
        );
    }

    protected function configureMocks(array $variantsData, array $productData)
    {
        $this->customFieldProvider->expects($this->any())
            ->method('getEntityCustomFields')
            ->with(Product::class)
            ->willReturn($variantsData);

        $this->enumHandler->expects($this->any())
            ->method('getPossibleValues')
            ->willReturnCallback(
                function ($fieldName) use ($variantsData) {
                    return isset($variantsData[$fieldName]['values']) ? $variantsData[$fieldName]['values'] : [];
                }
            );

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($message) {
                    return $message . '.trans';
                }
            );

        $this->configureRepositoryMock($variantsData, $productData);
    }

    protected function configureRepositoryMock(array $variantsData, array $productData)
    {
        $simpleProducts = $this->getSimpleProductsWithVariants($variantsData, $productData);

        $this->productRepository
            ->expects($this->any())
            ->method('getSimpleProductsByVariantFieldsQueryBuilder')
            ->willReturnCallback(
                function ($configurableProduct, array $variantParameters) use ($simpleProducts) {
                    $filteredProducts = array_filter(
                        $simpleProducts,
                        function (Product $simpleProduct) use ($variantParameters) {
                            foreach ($variantParameters as $name => $value) {
                                if ($simpleProduct->{$name} != $value) {
                                    return false;
                                }
                            }

                            return true;
                        }
                    );

                    $queryAvailableSimpleProducts = $this->getMockBuilder(AbstractQuery::class)
                        ->disableOriginalConstructor()
                        ->getMock();
                    $queryAvailableSimpleProducts
                        ->expects($this->any())
                        ->method('getResult')
                        ->willReturn($filteredProducts);
                    $qbAvailableSimpleProducts = $this->getMockBuilder(QueryBuilder::class)
                        ->disableOriginalConstructor()
                        ->getMock();
                    $qbAvailableSimpleProducts
                        ->expects($this->any())
                        ->method('getQuery')
                        ->willReturn($queryAvailableSimpleProducts);

                    return $qbAvailableSimpleProducts;
                }
            );
    }

    /**
     * @param array $variantsData
     * @param array $productData
     * @return Product[]
     */
    private function getSimpleProductsWithVariants(array $variantsData, array $productData)
    {
        $products = [];
        foreach ($productData as $sku => $data) {
            $products[] = $product = $this->getSimpleProduct();
            $product->setSku($sku);

            foreach ($data as $field => $value) {
                switch ($variantsData[$field]['type']) {
                    case 'enum':
                        $fieldValue = $value ? new TestEnumValue($value, $value) : null;
                        break;
                    case 'boolean':
                        $fieldValue = $value;
                        break;
                    default:
                        throw new \InvalidArgumentException('Unknown type');
                }

                $product->{$field} = $fieldValue;
            }
        }

        return $products;
    }

    protected function assertGetSimpleProductsCall(array $simpleProductResult)
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($simpleProductResult);

        /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $qb */
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->productRepository->expects($this->once())
            ->method('getSimpleProductIdsByParentProductsQueryBuilder')
            ->willReturn($qb);

        $event = new RestrictProductVariantEvent($qb);
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, RestrictProductVariantEvent::NAME);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getReference')
            ->with(Product::class)
            ->willReturnCallback(function ($class, $id) {
                return $this->getEntity($class, ['id' => $id]);
            });
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(Product::class)
            ->willReturn($em);
    }
}
