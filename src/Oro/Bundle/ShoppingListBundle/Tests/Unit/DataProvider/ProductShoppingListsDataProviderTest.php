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
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductShoppingListsDataProviderTest extends \PHPUnit\Framework\TestCase
{
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

    private function getShoppingList(int $id, string $label, bool $isCurrent): ShoppingList
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, $id);
        $shoppingList->setCustomerUser(new CustomerUser());
        $shoppingList->setLabel($label);
        $shoppingList->setCurrent($isCurrent);

        return $shoppingList;
    }

    private function getLineItem(
        int $id,
        string $unit,
        float $quantity,
        ShoppingList $shoppingList,
        Product $product,
        Product $parentProduct = null
    ): LineItem {
        $lineItem = new LineItem();
        ReflectionUtil::setId($lineItem, $id);
        $lineItem->setUnit($this->getProductUnit($unit));
        $lineItem->setQuantity($quantity);
        $lineItem->setShoppingList($shoppingList);
        $lineItem->setProduct($product);
        if (null !== $parentProduct) {
            $lineItem->setParentProduct($parentProduct);
        }

        return $lineItem;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $unit = new ProductUnit();
        $unit->setCode($code);

        return $unit;
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    /**
     * @dataProvider getProductUnitsQuantityDataProvider
     */
    public function testGetProductUnitsQuantityByCustomerUser(
        ?Product $product,
        ?ShoppingList $shoppingList,
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

        $this->currentShoppingListManager->expects($this->any())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->lineItemRepository->expects($product && $shoppingList ? $this->once() : $this->never())
            ->method('getProductItemsWithShoppingListNames')
            ->with($this->aclHelper, [$product->getId()], $customerUser)
            ->willReturn($lineItems);

        $this->assertEquals($expected, $this->provider->getProductUnitsQuantity($product->getId()));
    }

    /**
     * @dataProvider getProductUnitsQuantityDataProvider
     */
    public function testGetProductUnitsQuantityWithCustomerUserButAllShoppingLists(
        ?Product $product,
        ?ShoppingList $shoppingList,
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

        $this->currentShoppingListManager->expects($this->any())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->lineItemRepository->expects($product && $shoppingList ? $this->once() : $this->never())
            ->method('getProductItemsWithShoppingListNames')
            ->with($this->aclHelper, [$product->getId()])
            ->willReturn($lineItems);

        $this->assertEquals($expected, $this->provider->getProductUnitsQuantity($product->getId()));
    }

    public function getProductUnitsQuantityDataProvider(): array
    {
        $activeShoppingList = $this->getShoppingList(1, 'ShoppingList 1', true);
        $activeShoppingListSecond = $this->getShoppingList(1, 'ShoppingList 2', true);
        $otherShoppingList = $this->getShoppingList(2, 'ShoppingList 3', false);

        $product = $this->getProduct(1);
        $parentProduct = $this->getProduct(2);

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
                    $this->getLineItem(1, 'code1', 42, $activeShoppingList, $product),
                    $this->getLineItem(2, 'code2', 100, $activeShoppingList, $product)
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
                    $this->getLineItem(1, 'code1', 42, $activeShoppingListSecond, $product),
                    $this->getLineItem(2, 'code2', 100, $activeShoppingListSecond, $product),
                    $this->getLineItem(3, 'code3', 30, $otherShoppingList, $product),
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
                    $this->getLineItem(1, 'code1', 42, $activeShoppingListSecond, $product),
                    $this->getLineItem(2, 'code2', 30, $otherShoppingList, $product, $parentProduct),
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

        $this->currentShoppingListManager->expects($this->any())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->lineItemRepository->expects($this->never())
            ->method('getProductItemsWithShoppingListNames');

        $this->assertEquals(null, $this->provider->getProductUnitsQuantity(1));
    }

    public function testGetProductsUnitsQuantity()
    {
        $shoppingList = $this->getShoppingList(1, 'ShoppingList 1', true);
        $secondShoppingList = $this->getShoppingList(2, 'ShoppingList 2', false);

        $product = $this->getProduct(11);
        $secondSimpleProduct = $this->getProduct(41);
        $parentProduct = $this->getProduct(21);
        $otherProduct = $this->getProduct(31);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_shopping_list.show_all_in_shopping_list_widget')
            ->willReturn(true);

        $this->currentShoppingListManager->expects($this->once())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $lineItems = [
            $this->getLineItem(1, 'each', 10, $shoppingList, $product, $parentProduct),
            $this->getLineItem(2, 'each', 100, $shoppingList, $secondSimpleProduct, $parentProduct),
            $this->getLineItem(3, 'each', 1, $secondShoppingList, $otherProduct),
        ];

        $this->lineItemRepository->expects($this->once())
            ->method('getProductItemsWithShoppingListNames')
            ->with(
                $this->aclHelper,
                [$product->getId(), $parentProduct->getId(), $otherProduct->getId(), $secondSimpleProduct->getId()]
            )
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
            $this->provider->getProductsUnitsQuantity([
                $product->getId(),
                $parentProduct->getId(),
                $otherProduct->getId(),
                $secondSimpleProduct->getId()
            ])
        );
    }
}
