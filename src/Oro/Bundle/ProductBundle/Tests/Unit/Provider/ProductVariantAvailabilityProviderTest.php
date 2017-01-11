<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Component\Testing\Unit\Entity\Stub\StubEnumValue;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductVariantAvailabilityProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductVariantAvailabilityProvider */
    protected $availabilityProvider;

    /** @var  EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $productRepository;

    /** @var  QueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $qb;

    /** @var  AbstractQuery|\PHPUnit_Framework_MockObject_MockObject */
    protected $query;

    /** @var  PropertyAccessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $propertyAccessor;

    /** @var  DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var  EnumValueProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $enumValueProvider;

    /** @var  CustomFieldProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $customFieldProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->productRepository);

        $this->customFieldProvider = $this->getMockBuilder(CustomFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->enumValueProvider = $this->getMockBuilder(EnumValueProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->propertyAccessor = $this->getMockBuilder(PropertyAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $eventDispatcher = new EventDispatcher();

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
            $this->doctrineHelper,
            $this->enumValueProvider,
            $this->customFieldProvider,
            $this->propertyAccessor,
            $eventDispatcher
        );
    }

    /**
     * @return Product
     */
    protected function getConfigurableProduct()
    {
        $configurableProduct = new Product();
        $configurableProduct->setType(Product::TYPE_CONFIGURABLE);

        return $configurableProduct;
    }

    /**
     * @return Product
     */
    protected function getSimpleProduct()
    {
        $simpleProduct = new Product();
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

    /**
     * @param array $variantsData
     * @param array $productData
     * @param array $variantParameters
     * @param array $variantFields
     * @param array $expected
     * @dataProvider variantFieldsWithAvailabilityProvider
     */
    public function testGetVariantFieldsWithAvailability(
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
            $this->availabilityProvider->getVariantFieldsWithAvailability($configurableProduct, $variantParameters)
        );
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function variantFieldsWithAvailabilityProvider()
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
                        'size' => 's',
                        'slim_fit' => true,
                    ],
                    'product3' => [
                        'color' => 'green',
                        'size' => 'm',
                        'slim_fit' => true,
                    ],
                    'product4' => [
                        'color' => 'green',
                        'size' => 'm',
                        'slim_fit' => false,
                    ],
                    'product5' => [
                        'color' => 'red',
                        'size' => 'xl',
                        'slim_fit' => false,
                    ],
                ],
                'variantParameters' => [
                    'size' => 'm',
                    'color' => 'green',
                    'slim_fit' => false
                ],
                'variantFields' => [
                    'color',
                    'size',
                    'slim_fit',
                ],
                'expected' => [
                    'size' => [
                        's' => false,
                        'm' => true,
                        'l' => false,
                        'xl' => false,
                    ],
                    'color' => [
                        'red' => false,
                        'green' => true,
                        'blue' => false,
                    ],
                    'slim_fit' => [
                        0 => true,
                        1 => true
                    ]
                ],
            ],
            'variant 3' => [
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
                        'red' => true,
                        'green' => true,
                        'blue' => false,
                    ]
                ],
            ],
        ];
    }

    protected function configureMocks($variantsData, $productData)
    {
        $this->propertyAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnCallback(
                function (Product $product, $fieldName) use ($productData, $variantsData) {
                    foreach ($productData as $mockProductSku => $mockProductData) {
                        if ($mockProductSku !== $product->getSku()) {
                            continue;
                        }

                        switch ($variantsData[$fieldName]['type']) {
                            case 'enum':
                                return new StubEnumValue($mockProductData[$fieldName], $mockProductData[$fieldName]);

                            case 'boolean':
                                return $mockProductData[$fieldName];
                        }
                    }
                }
            );

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(
                function (StubEnumValue $variantValue) {
                    return $variantValue->getId();
                }
            );

        $this->enumValueProvider->expects($this->any())
            ->method('getEnumChoicesByCode')
            ->willReturnCallback(
                function ($enumCode) use ($variantsData) {
                    $matches = [];
                    preg_match('/(?<=product_).+(?=_\w+)/', $enumCode, $matches);
                    $fieldName = $matches[0];

                    return isset($variantsData[$fieldName]['values']) ? $variantsData[$fieldName]['values'] : [];
                }
            );

        $this->customFieldProvider->expects($this->any())
            ->method('getEntityCustomFields')
            ->with(Product::class)
            ->willReturn($variantsData);

        $this->configureRepositoryMock($productData);
    }

    protected function configureRepositoryMock($productData)
    {
        $this->productRepository
            ->expects($this->any())
            ->method('getSimpleProductsByVariantFieldsQueryBuilder')
            ->willReturnCallback(
                function ($configurableProduct, $variantParameters) use ($productData) {
                    $products = [];
                    foreach ($productData as $mockProductSku => $mockProductData) {
                        foreach ($variantParameters as $variantName => $variantValue) {
                            if (!isset($mockProductData[$variantName]) ||
                                $mockProductData[$variantName] !== $variantValue
                            ) {
                                continue 2;
                            }
                        }

                        $simpleProduct = $this->getSimpleProduct();
                        $simpleProduct->setSku($mockProductSku);
                        $products[] = $simpleProduct;
                    }

                    $queryAvailableSimpleProducts = $this->getMockBuilder(AbstractQuery::class)
                        ->disableOriginalConstructor()
                        ->getMock();
                    $queryAvailableSimpleProducts
                        ->expects($this->any())
                        ->method('getResult')
                        ->willReturn($products);
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
}
