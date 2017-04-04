<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\BooleanVariantFieldValueHandler;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\EnumVariantFieldValueHandler;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ProductVariantAvailabilityProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductVariantAvailabilityProvider */
    protected $availabilityProvider;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $productRepository;

    /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $qb;

    /** @var AbstractQuery|\PHPUnit_Framework_MockObject_MockObject */
    protected $query;

    /** @var CustomFieldProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $customFieldProvider;

    /** @var EnumVariantFieldValueHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $enumHandler;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->productRepository);

        $doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(
                function ($variantValue) {
                    return $variantValue->getId();
                }
            );

        $this->customFieldProvider = $this->getMockBuilder(CustomFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EnumValueProvider|\PHPUnit_Framework_MockObject_MockObject $enumValueProvider */
        $enumValueProvider = $this->getMockBuilder(EnumValueProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $variantFieldValueHandlerRegistry = new ProductVariantFieldValueHandlerRegistry();
        $boolHandler = new BooleanVariantFieldValueHandler();
        $this->enumHandler = $this->getMockBuilder(EnumVariantFieldValueHandler::class)
            ->setConstructorArgs([$doctrineHelper, $enumValueProvider])
            ->setMethods(['getPossibleValues'])
            ->getMock();
        $variantFieldValueHandlerRegistry->addHandler($boolHandler);
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

        $this->availabilityProvider = new ProductVariantAvailabilityProvider(
            $doctrineHelper,
            $this->customFieldProvider,
            $propertyAccessor,
            $this->dispatcher,
            $variantFieldValueHandlerRegistry
        );
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        unset(
            $availabilityProvider,
            $productRepository,
            $qb,
            $query,
            $customFieldProvider,
            $enumHandler,
            $dispatcher
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

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @param array $result
     */
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
            ->with(RestrictProductVariantEvent::NAME, $this->isInstanceOf(RestrictProductVariantEvent::class));

        $this->setUpRepositoryResult($configurableProduct, $variantParameters, $expected);

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

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Variant values provided don't match exactly one simple product
     */
    public function testGetSimpleProductByVariantFieldsSeveralProducts()
    {
        $configurableProduct = $this->getConfigurableProduct();

        $products = [
            $this->getSimpleProduct(),
            $this->getSimpleProduct(),
        ];

        $this->setUpRepositoryResult($configurableProduct, [], $products);

        $this->availabilityProvider->getSimpleProductByVariantFields($configurableProduct);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Variant values provided don't match exactly one simple product
     */
    public function testGetSimpleProductByVariantFieldsNoProducts()
    {
        $configurableProduct = $this->getConfigurableProduct();

        $products = [];

        $this->setUpRepositoryResult($configurableProduct, [], $products);

        $this->availabilityProvider->getSimpleProductByVariantFields($configurableProduct);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Product with type "configurable" expected, "simple" given
     */
    public function testGetSimpleProductByVariantFieldsWrongProductType()
    {
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
                            'red' => 'Red',
                            'green' => 'Green',
                            'blue' => 'Blue',
                        ]
                    ],
                    'size' => [
                        'type' => 'enum',
                        'values' => [
                            's' => 'S',
                            'm' => 'M',
                            'l' => 'L',
                            'xl' => 'XL',
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
                        'color' => 'red',
                        'size' => 's',
                        'slim_fit' => true,
                    ],
                    'product2' => [
                        'color' => 'green',
                        'size' => 'm',
                        'slim_fit' => false,
                    ],
                ],
                'variantParameters' => [
                    'size' => 's',
                    'color' => 'red',
                    'slim_fit' => true
                ],
                'variantFields' => [
                    'color',
                    'size',
                    'slim_fit',
                ],
                'expected' => [
                    'size' => [
                        's' => true,
                        'm' => false,
                        'l' => false,
                        'xl' => false,
                    ],
                    'color' => [
                        'red' => true,
                        'green' => false,
                        'blue' => false,
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
                            'red' => 'Red',
                            'green' => 'Green',
                            'blue' => 'Blue',
                        ]
                    ],
                    'extended_field' => [
                        'type' => 'string',
                    ],
                ],
                'productData' => [
                    'product1' => [
                        'color' => 'red'
                    ],
                    'product2' => [
                        'color' => 'green'
                    ],
                    'product3' => [
                        'color' => null
                    ],
                ],
                'variantParameters' => [
                    'color' => 'green',
                ],
                'variantFields' => [
                    'color',
                ],
                'expected' => [
                    'color' => [
                        'red' => false,
                        'green' => true,
                        'blue' => false,
                    ]
                ],
            ],
        ];
    }

    /**
     * @param array $variantsData
     * @param array $productData
     */
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

        $this->configureRepositoryMock($variantsData, $productData);
    }

    /**
     * @param array $variantsData
     * @param array $productData
     */
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
                        $fieldValue = $value ? new StubEnumValue($value, $value) : null;
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
}
