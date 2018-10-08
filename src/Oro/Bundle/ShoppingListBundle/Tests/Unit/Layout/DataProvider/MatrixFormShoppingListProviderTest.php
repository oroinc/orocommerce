<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixFormShoppingListProvider;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderFormProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormView;

class MatrixFormShoppingListProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var MatrixGridOrderFormProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $matrixGridOrderFormProvider;

    /** @var ProductFormAvailabilityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $productFormAvailabilityProvider;

    /** @var MatrixFormShoppingListProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->matrixGridOrderFormProvider = $this->createMock(MatrixGridOrderFormProvider::class);
        $this->productFormAvailabilityProvider = $this->createMock(ProductFormAvailabilityProvider::class);

        $this->provider = new MatrixFormShoppingListProvider(
            $this->matrixGridOrderFormProvider,
            $this->productFormAvailabilityProvider
        );
    }

    /**
     * @param Product[] $products
     * @param LineItem[] $lineItems
     * @param FormView|\PHPUnit\Framework\MockObject\MockObject $formView
     * @param bool $formOption
     * @param string $isFormAvailable
     * @param bool $isMobile
     * @param array $expected
     * @dataProvider getSortedLineItemsProvider
     */
    public function testGetSortedLineItems(
        $products,
        $lineItems,
        $formView,
        $formOption,
        $isFormAvailable,
        $isMobile,
        $expected
    ) {
        $shoppingList = $this->getEntity(
            ShoppingList::class,
            ['lineItems' => $lineItems]
        );

        $this->productFormAvailabilityProvider->expects($this->any())
            ->method('getAvailableMatrixFormType')
            ->willReturnCallback(function (Product $product) use ($formOption, $isFormAvailable, $isMobile) {
                if ($product->getType() === Product::TYPE_CONFIGURABLE && $isFormAvailable) {
                    return $isMobile ? Configuration::MATRIX_FORM_POPUP : $formOption;
                } else {
                    return Configuration::MATRIX_FORM_NONE;
                }
            });

        $this->matrixGridOrderFormProvider->expects($this->any())
            ->method('getMatrixOrderFormView')
            ->with($products['parentProduct'], $shoppingList)
            ->willReturn($formView);

        $this->provider->getSortedLineItems($shoppingList);
        $this->assertSame($expected, $this->provider->getSortedLineItems($shoppingList));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function getSortedLineItemsProvider()
    {
        $productUnitEach = (new ProductUnit())->setCode('each');
        $productUnitItem = (new ProductUnit())->setCode('item');

        $productUnitPrecisionEach = (new ProductUnitPrecision())->setUnit($productUnitEach);
        $productUnitPrecisionItem = (new ProductUnitPrecision())->setUnit($productUnitItem);

        $products = [];
        $products['parentProduct'] = $this->getEntity(Product::class, [
            'type' => Product::TYPE_CONFIGURABLE,
            'id' => 1,
            'primaryUnitPrecision' => $productUnitPrecisionEach
        ]);

        $products['variantProduct1'] = $this->getEntity(Product::class, [
            'type' => Product::TYPE_SIMPLE,
            'id' => 11,
            'primaryUnitPrecision' => $productUnitPrecisionEach
        ]);

        $products['variantProduct2'] = $this->getEntity(Product::class, [
            'type' => Product::TYPE_SIMPLE,
            'id' => 12,
            'primaryUnitPrecision' => $productUnitPrecisionEach
        ]);

        $products['simpleProduct3'] = $this->getEntity(Product::class, [
            'type' => Product::TYPE_SIMPLE,
            'id' => 13,
            'primaryUnitPrecision' => $productUnitPrecisionItem
        ]);

        $products['simpleProduct4'] = $this->getEntity(Product::class, [
            'type' => Product::TYPE_SIMPLE,
            'id' => 14,
            'primaryUnitPrecision' => $productUnitPrecisionItem
        ]);

        $products['parentProductWrongUnits'] = $this->getEntity(Product::class, [
            'type' => Product::TYPE_CONFIGURABLE,
            'id' => 15,
            'primaryUnitPrecision' => $productUnitPrecisionEach
        ]);

        $products['variantProductWrongUnits'] = $this->getEntity(Product::class, [
            'type' => Product::TYPE_SIMPLE,
            'id' => 16,
            'primaryUnitPrecision' => $productUnitPrecisionEach
        ]);

        $lineItems = [];
        $lineItems['lineItem1'] = $this->getEntity(
            LineItem::class,
            [
                'id' => 1,
                'product' => $products['simpleProduct3'],
                'unit' => $productUnitItem
            ]
        );
        $lineItems['lineItem2'] = $this->getEntity(
            LineItem::class,
            [
                'id' => 2,
                'parentProduct' => $products['parentProduct'],
                'product' => $products['variantProduct1'],
                'unit' => $productUnitEach
            ]
        );
        $lineItems['lineItem3'] = $this->getEntity(
            LineItem::class,
            [
                'id' => 3,
                'product' => $products['simpleProduct4'],
                'unit' => $productUnitItem
            ]
        );
        $lineItems['lineItem4'] = $this->getEntity(
            LineItem::class,
            [
                'id' => 4,
                'parentProduct' => $products['parentProduct'],
                'product' => $products['variantProduct2'],
                'unit' => $productUnitEach
            ]
        );
        $lineItems['lineItem5'] = $this->getEntity(
            LineItem::class,
            [
                'id' => 5,
                'parentProduct' => $products['parentProductWrongUnits'],
                'product' => $products['variantProductWrongUnits'],
                'unit' => $productUnitItem
            ]
        );

        $formView = $this->createMock(FormView::class);

        return [
            'desktop, config inline, matrix not available' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => false,
                'isMobile' => false,
                'expected' => [
                    '11:each' => [
                        'lineItems' => [$lineItems['lineItem2']],
                        'product' => $products['variantProduct1'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '12:each' => [
                        'lineItems' => [$lineItems['lineItem4']],
                        'product' => $products['variantProduct2'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '16:item' => [
                        'lineItems' => [$lineItems['lineItem5']],
                        'product' => $products['variantProductWrongUnits'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                ],
            ],
            'desktop, config inline, matrix available' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => true,
                'isMobile' => false,
                'expected' => [
                    '16:item' => [
                        'lineItems' => [$lineItems['lineItem5']],
                        'product' => $products['variantProductWrongUnits'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '1:each' => [
                        'matrixForm' => $formView,
                        'lineItems' => [$lineItems['lineItem2'], $lineItems['lineItem4']],
                        'product' => $products['parentProduct'],
                        'matrixFormType' => Configuration::MATRIX_FORM_INLINE,
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ]
                ],
            ],
            'desktop, config popup, matrix not available' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => false,
                'isMobile' => false,
                'expected' => [
                    '11:each' => [
                        'lineItems' => [$lineItems['lineItem2']],
                        'product' => $products['variantProduct1'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '12:each' => [
                        'lineItems' => [$lineItems['lineItem4']],
                        'product' => $products['variantProduct2'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '16:item' => [
                        'lineItems' => [$lineItems['lineItem5']],
                        'product' => $products['variantProductWrongUnits'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                ],
            ],
            'desktop, config popup, matrix available' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => true,
                'isMobile' => false,
                'expected' => [
                    '16:item' => [
                        'lineItems' => [$lineItems['lineItem5']],
                        'product' => $products['variantProductWrongUnits'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '1:each' => [
                        'lineItems' => [$lineItems['lineItem2'], $lineItems['lineItem4']],
                        'product' => $products['parentProduct'],
                        'matrixFormType' => Configuration::MATRIX_FORM_POPUP,
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ]
                ],
            ],
            'desktop, config none, matrix available' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => Configuration::MATRIX_FORM_NONE,
                'isMatrixFormAvailable' => true,
                'isMobile' => false,
                'expected' => [
                    '11:each' => [
                        'lineItems' => [$lineItems['lineItem2']],
                        'product' => $products['variantProduct1'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '12:each' => [
                        'lineItems' => [$lineItems['lineItem4']],
                        'product' => $products['variantProduct2'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '16:item' => [
                        'lineItems' => [$lineItems['lineItem5']],
                        'product' => $products['variantProductWrongUnits'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                ],
            ],
            'mobile, config inline, matrix available' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => true,
                'isMobile' => true,
                'expected' => [
                    '16:item' => [
                        'lineItems' => [$lineItems['lineItem5']],
                        'product' => $products['variantProductWrongUnits'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '1:each' => [
                        'lineItems' => [$lineItems['lineItem2'], $lineItems['lineItem4']],
                        'product' => $products['parentProduct'],
                        'matrixFormType' => Configuration::MATRIX_FORM_POPUP,
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ]
                ],
            ],
            'mobile, config inline, matrix not available' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => Configuration::MATRIX_FORM_INLINE,
                'isMatrixFormAvailable' => false,
                'isMobile' => true,
                'expected' => [
                    '11:each' => [
                        'lineItems' => [$lineItems['lineItem2']],
                        'product' => $products['variantProduct1'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '12:each' => [
                        'lineItems' => [$lineItems['lineItem4']],
                        'product' => $products['variantProduct2'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '16:item' => [
                        'lineItems' => [$lineItems['lineItem5']],
                        'product' => $products['variantProductWrongUnits'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ]
                ],
            ],
            'mobile, config popup, matrix available' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => Configuration::MATRIX_FORM_POPUP,
                'isMatrixFormAvailable' => true,
                'isMobile' => true,
                'expected' => [
                    '16:item' => [
                        'lineItems' => [$lineItems['lineItem5']],
                        'product' => $products['variantProductWrongUnits'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ],
                    '1:each' => [
                        'lineItems' => [$lineItems['lineItem2'], $lineItems['lineItem4']],
                        'product' => $products['parentProduct'],
                        'matrixFormType' => Configuration::MATRIX_FORM_POPUP,
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4'],
                        'matrixFormType' => Configuration::MATRIX_FORM_NONE,
                    ]
                ],
            ],
        ];
    }
}
