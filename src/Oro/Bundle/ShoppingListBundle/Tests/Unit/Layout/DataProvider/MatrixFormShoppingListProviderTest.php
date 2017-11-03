<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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

class MatrixFormShoppingListProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var MatrixGridOrderFormProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $matrixGridOrderFormProvider;

    /** @var ProductFormAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $productFormAvailabilityProvider;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var MatrixFormShoppingListProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->matrixGridOrderFormProvider = $this->createMock(MatrixGridOrderFormProvider::class);
        $this->productFormAvailabilityProvider = $this->createMock(ProductFormAvailabilityProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new MatrixFormShoppingListProvider(
            $this->matrixGridOrderFormProvider,
            $this->productFormAvailabilityProvider,
            $this->configManager
        );
    }

    /**
     * @param Product[] $products
     * @param LineItem[] $lineItems
     * @param FormView|\PHPUnit_Framework_MockObject_MockObject $formView
     * @param bool $matrixFormOption
     * @param string $isMatrixFormAvailable
     * @param array $expected
     * @dataProvider getSortedLineItemsProvider
     */
    public function testGetSortedLineItems(
        $products,
        $lineItems,
        $formView,
        $matrixFormOption,
        $isMatrixFormAvailable,
        $expected
    ) {
        $shoppingList = $this->getEntity(
            ShoppingList::class,
            ['lineItems' => $lineItems]
        );

        $this->productFormAvailabilityProvider->expects($this->any())
            ->method('isMatrixFormAvailable')
            ->withConsecutive(
                [$products['simpleProduct3']],
                [$products['parentProduct']],
                [$products['simpleProduct4']]
            )
            ->willReturnOnConsecutiveCalls(
                false,
                $isMatrixFormAvailable,
                false
            );

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_product.matrix_form_on_shopping_list')
            ->willReturn($matrixFormOption);

        $this->matrixGridOrderFormProvider->expects($this->any())
            ->method('getMatrixOrderFormView')
            ->with($products['parentProduct'], $shoppingList)
            ->willReturn($formView);

        $this->assertEquals($expected, $this->provider->getSortedLineItems($shoppingList));
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

        $products['variantProduct1'] = $this->getEntity(
            Product::class,
            [
                'type' => Product::TYPE_SIMPLE,
                'id' => 11,
                'primaryUnitPrecision' => $productUnitPrecisionEach
            ]
        );

        $products['variantProduct2'] = $this->getEntity(
            Product::class,
            [
                'type' => Product::TYPE_SIMPLE,
                'id' => 12,
                'primaryUnitPrecision' => $productUnitPrecisionEach
            ]
        );

        $products['simpleProduct3'] = $this->getEntity(
            Product::class,
            [
                'type' => Product::TYPE_SIMPLE,
                'id' => 13,
                'primaryUnitPrecision' => $productUnitPrecisionItem
            ]
        );

        $products['simpleProduct4'] = $this->getEntity(
            Product::class,
            [
                'type' => Product::TYPE_SIMPLE,
                'id' => 14,
                'primaryUnitPrecision' => $productUnitPrecisionItem
            ]
        );

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

        $formView = $this->createMock(FormView::class);

        return [
            'without InlineMatrixForm' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => 'inline',
                'isMatrixFormAvailable' => false,
                'expected' => [
                    '11:each' => [
                        'lineItems' => [$lineItems['lineItem2']],
                        'product' => $products['variantProduct1']
                    ],
                    '12:each' => [
                        'lineItems' => [$lineItems['lineItem4']],
                        'product' => $products['variantProduct2']
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3']
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4']
                    ],
                ],
            ],
            'with InlineMatrixForm' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => 'inline',
                'isMatrixFormAvailable' => true,
                'expected' => [
                    '1:each' => [
                        'lineItems' => [$lineItems['lineItem2'], $lineItems['lineItem4']],
                        'product' => $products['parentProduct'],
                        'form' => $formView
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3']
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4']
                    ]
                ],
            ],
            'with Inline Option Disabled' => [
                'products' => $products,
                'lineItems' => $lineItems,
                'formView' => $formView,
                'matrixFormOption' => 'group',
                'isMatrixFormAvailable' => true,
                'expected' => [
                    '11:each' => [
                        'lineItems' => [$lineItems['lineItem2']],
                        'product' => $products['variantProduct1']
                    ],
                    '12:each' => [
                        'lineItems' => [$lineItems['lineItem4']],
                        'product' => $products['variantProduct2']
                    ],
                    '13:item' => [
                        'lineItems' => [$lineItems['lineItem1']],
                        'product' => $products['simpleProduct3']
                    ],
                    '14:item' => [
                        'lineItems' => [$lineItems['lineItem3']],
                        'product' => $products['simpleProduct4']
                    ],
                ],
            ],
        ];
    }
}
