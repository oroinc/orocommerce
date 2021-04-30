<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\CustomerBundle\Tests\Unit\Stub\CustomerUserStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductShoppingListsDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CurrentShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $currentShoppingListManager;

    /** @var LineItemRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $lineItemRepository;

    /** @var AclHelper */
    private $aclHelper;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ProductShoppingListsDataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->provider = new ProductShoppingListsDataProvider(
            $this->currentShoppingListManager,
            $this->lineItemRepository,
            $this->aclHelper,
            $this->tokenAccessor,
            $this->configManager
        );
    }

    /**
     * @dataProvider getProductUnitsQuantityDataProvider
     *
     * @param Product|null $product
     * @param ShoppingList|null $shoppingList
     * @param array $lineItems
     * @param array|null $expected
     */
    public function testGetProductUnitsQuantityByCustomerUser(
        $product,
        $shoppingList,
        array $lineItems = [],
        array $expected = null
    ): void {
        $customerUser = new CustomerUserStub();

        $this->tokenAccessor->expects($this->any())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_shopping_list.show_all_in_shopping_list_widget')
            ->willReturn(false);

        $this->currentShoppingListManager
            ->expects($this->any())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->lineItemRepository->expects($product && $shoppingList ? $this->once() : $this->never())
            ->method('getProductItemsWithShoppingListNames')
            ->with($this->aclHelper, [$product], $customerUser)
            ->willReturn($lineItems);

        $this->assertEquals($expected, $this->provider->getProductUnitsQuantity($product));
    }

    /**
     * @dataProvider getProductUnitsQuantityDataProvider
     *
     * @param Product|null $product
     * @param ShoppingList|null $shoppingList
     * @param array $lineItems
     * @param array|null $expected
     */
    public function testGetProductUnitsQuantityWithCustomerUserButAllShoppingLists(
        $product,
        $shoppingList,
        array $lineItems = [],
        array $expected = null
    ): void {
        $customerUser = new CustomerUserStub();

        $this->tokenAccessor->expects($this->any())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));
        $this->tokenAccessor->expects($this->any())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_shopping_list.show_all_in_shopping_list_widget')
            ->willReturn(true);

        $this->currentShoppingListManager
            ->expects($this->any())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->lineItemRepository->expects($product && $shoppingList ? $this->once() : $this->never())
            ->method('getProductItemsWithShoppingListNames')
            ->with($this->aclHelper, [$product])
            ->willReturn($lineItems);

        $this->assertEquals($expected, $this->provider->getProductUnitsQuantity($product));
    }

    /**
     * @return array
     */
    public function getProductUnitsQuantityDataProvider()
    {
        /** @var  ShoppingList $activeShoppingList */
        $activeShoppingList = $this->createShoppingList(1, 'ShoppingList 1', true);
        /** @var  ShoppingList $activeShoppingListSecond */
        $activeShoppingListSecond = $this->createShoppingList(1, 'ShoppingList 2', true);
        /** @var  ShoppingList $otherShoppingList */
        $otherShoppingList = $this->createShoppingList(2, 'ShoppingList 3', false);

        $product = $this->getEntity(Product::class, ['id' => 1]);
        $parentProduct = $this->getEntity(Product::class, ['id' => 2]);

        return [
            'no_shopping_list' => [
                'product' => $product,
                'shoppingList' => null
            ],
            'no_prices' => [
                'product' => $product,
                'shoppingList' => $otherShoppingList,
                'lineItems' => []
            ],
            'single_shopping_list' => [
                'product' => $product,
                'shoppingList' => $activeShoppingList,
                'lineItems' => [
                    $this->createLineItem(1, 'code1', 42, $activeShoppingList, $product),
                    $this->createLineItem(2, 'code2', 100, $activeShoppingList, $product)
                ],
                'expected' => [
                    [
                        'id' => 1,
                        'label' => 'ShoppingList 1',
                        'is_current' => true,
                        'line_items' => [
                            ['id' => 1, 'unit' => 'code1', 'quantity' => 42, 'productId' => 1],
                            ['id' => 2, 'unit' => 'code2', 'quantity' => 100, 'productId' => 1],
                        ]
                    ]
                ]
            ],
            'a_few_shopping_lists' => [
                'product' => $product,
                'shoppingList' => $activeShoppingListSecond,
                'lineItems' => [
                    $this->createLineItem(1, 'code1', 42, $activeShoppingListSecond, $product),
                    $this->createLineItem(2, 'code2', 100, $activeShoppingListSecond, $product),
                    $this->createLineItem(3, 'code3', 30, $otherShoppingList, $product),
                ],
                'expected' => [
                    [
                        'id' => 1,
                        'label' => 'ShoppingList 2',
                        'is_current' => true,
                        'line_items' => [
                            ['id' => 1, 'unit' => 'code1', 'quantity' => 42, 'productId' => 1],
                            ['id' => 2,'unit' => 'code2', 'quantity' => 100, 'productId' => 1],
                        ]
                    ],
                    [
                        'id' => 2,
                        'label' => 'ShoppingList 3',
                        'is_current' => false,
                        'line_items' => [
                            ['id' => 3, 'unit' => 'code3', 'quantity' => 30, 'productId' => 1],
                        ]
                    ]
                ]
            ],
            'shipping_lists_for_product_added_as_simple_and_configurable_in_different_shopping_lists' => [
                'product' => $product,
                'shoppingList' => $activeShoppingListSecond,
                'lineItems' => [
                    $this->createLineItem(1, 'code1', 42, $activeShoppingListSecond, $product),
                    $this->createLineItem(2, 'code2', 30, $otherShoppingList, $product, $parentProduct),
                ],
                'expected' => [
                    [
                        'id' => 1,
                        'label' => 'ShoppingList 2',
                        'is_current' => true,
                        'line_items' => [
                            ['id' => 1, 'unit' => 'code1', 'quantity' => 42, 'productId' => 1],
                        ]
                    ],
                    [
                        'id' => 2,
                        'label' => 'ShoppingList 3',
                        'is_current' => false,
                        'line_items' => [
                            ['id' => 2, 'unit' => 'code2', 'quantity' => 30, 'productId' => 1],
                        ]
                    ]
                ]
            ],
        ];
    }

    public function testGetProductUnitsQuantityForGuestUser()
    {
        $shoppingList = new ShoppingList();

        $this->tokenAccessor->expects($this->any())
            ->method('getToken')
            ->willReturn($this->createMock(AnonymousCustomerUserToken::class));

        $this->currentShoppingListManager
            ->expects($this->any())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->lineItemRepository->expects($this->never())
            ->method('getProductItemsWithShoppingListNames');

        $this->assertEquals(null, $this->provider->getProductUnitsQuantity(new Product()));
    }

    public function testGetProductsUnitsQuantity()
    {
        $shoppingList = $this->createShoppingList(1, 'ShoppingList 1', true);
        $secondShoppingList = $this->createShoppingList(2, 'ShoppingList 2', false);

        $product = $this->getEntity(Product::class, ['id' => 11]);
        $secondSimpleProduct = $this->getEntity(Product::class, ['id' => 41]);
        $parentProduct = $this->getEntity(Product::class, ['id' => 21]);
        $otherProduct = $this->getEntity(Product::class, ['id' => 31]);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_shopping_list.show_all_in_shopping_list_widget')
            ->willReturn(true);

        $this->currentShoppingListManager
            ->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $lineItems = [
            $this->createLineItem(1, 'each', 10, $shoppingList, $product, $parentProduct),
            $this->createLineItem(2, 'each', 100, $shoppingList, $secondSimpleProduct, $parentProduct),
            $this->createLineItem(3, 'each', 1, $secondShoppingList, $otherProduct),
        ];

        $this->lineItemRepository->expects($this->once())
            ->method('getProductItemsWithShoppingListNames')
            ->with($this->aclHelper, [$product, $parentProduct, $otherProduct, $secondSimpleProduct])
            ->willReturn($lineItems);

        $expected = [
            11 => [
                [
                    'id' => 1,
                    'label' => 'ShoppingList 1',
                    'is_current' => true,
                    'line_items' => [
                        ['id' => 1, 'unit' => 'each', 'quantity' => 10, 'productId' => 11],
                    ]
                ]
            ],
            21 => [
                [
                    'id' => 1,
                    'label' => 'ShoppingList 1',
                    'is_current' => true,
                    'line_items' => [
                        ['id' => 1, 'unit' => 'each', 'quantity' => 10, 'productId' => 11],
                        ['id' => 2, 'unit' => 'each', 'quantity' => 100, 'productId' => 41],
                    ]
                ]
            ],
            31 => [
                [
                    'id' => 2,
                    'label' => 'ShoppingList 2',
                    'is_current' => false,
                    'line_items' => [
                        ['id' => 3, 'unit' => 'each', 'quantity' => 1, 'productId' => 31],
                    ]
                ]
            ],
            41 => [
                [
                    'id' => 1,
                    'label' => 'ShoppingList 1',
                    'is_current' => true,
                    'line_items' => [
                        ['id' => 2, 'unit' => 'each', 'quantity' => 100, 'productId' => 41],
                    ]
                ]
            ]
        ];

        $this->assertEquals(
            $expected,
            $this->provider->getProductsUnitsQuantity(
                [
                    $product,
                    $parentProduct,
                    $otherProduct,
                    $secondSimpleProduct
                ]
            )
        );
    }

    /**
     * @param int $id
     * @param string $unit
     * @param int $quantity
     * @param ShoppingList $shoppingList
     * @param object $product
     * @param object|null $parentProduct
     * @return object
     */
    private function createLineItem($id, $unit, $quantity, $shoppingList, $product, $parentProduct = null)
    {
        $productUnit = $this->getEntity(ProductUnit::class, [
            'code' => $unit,
        ]);

        $options = [
            'id' => $id,
            'unit' => $productUnit,
            'quantity' => $quantity,
            'shoppingList' => $shoppingList,
            'product' => $product
        ];

        if (null !== $parentProduct) {
            $options['parentProduct'] = $parentProduct;
        }

        return $this->getEntity(LineItem::class, $options);
    }

    /**
     * @param int $id
     * @param string $label
     * @param boolean $isCurrent
     * @return object
     */
    protected function createShoppingList($id, $label, $isCurrent)
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => $id, 'customerUser' => new CustomerUser()]);

        $shoppingList
            ->setLabel($label)
            ->setCurrent($isCurrent);

        return $shoppingList;
    }
}
