<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Converter\OrderLineItemConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\InventoryBundle\Provider\InventoryQuantityProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OrderLineItemConverterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const CONFIG_PATH = 'oro_product.general_frontend_product_visibility';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var InventoryQuantityProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $quantityProvider;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var EntityFallbackResolver|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityFallbackResolver;

    /** @var OrderLineItemConverter */
    protected $converter;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects($this->any())
            ->method('get')
            ->with(self::CONFIG_PATH)
            ->willReturn([Product::INVENTORY_STATUS_IN_STOCK]);

        $this->quantityProvider = $this->createMock(InventoryQuantityProviderInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->entityFallbackResolver = $this->createMock(EntityFallbackResolver::class);

        $this->converter = new OrderLineItemConverter(
            $this->configManager,
            $this->quantityProvider,
            $this->authorizationChecker,
            $this->entityFallbackResolver,
            self::CONFIG_PATH
        );
    }

    public function testIsSourceSupported()
    {
        $this->assertTrue($this->converter->isSourceSupported(new Order()));
        $this->assertFalse($this->converter->isSourceSupported(new \stdClass()));
    }

    /**
     * @dataProvider convertDataProvider
     *
     * @param array $orderLineItems
     * @param bool $canDecrement
     * @param int $availableQuantity
     * @param bool $isVisible
     * @param array $checkoutLineItems
     */
    public function testConvert(
        array $orderLineItems,
        $canDecrement,
        $availableQuantity,
        $isVisible,
        array $checkoutLineItems,
        $allowBackorders = false
    ) {
        $this->quantityProvider->expects($this->any())
            ->method('canDecrement')
            ->willReturnCallback(
                function (Product $product) use ($canDecrement) {
                    return $canDecrement && $product->getId() === 3;
                }
            );

        $this->quantityProvider->expects($this->any())
            ->method('getAvailableQuantity')
            ->willReturnCallback(
                function (Product $product) use ($availableQuantity) {
                    return $product->getId() === 3 ? $availableQuantity : 0;
                }
            );

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(
                function ($argument, Product $product) use ($isVisible) {
                    return $isVisible && $argument === 'VIEW' && $product->getId() === 3;
                }
            );

        $this->entityFallbackResolver->expects($this->any())
            ->method('getFallbackValue')
            ->with($this->isInstanceOf(Product::class), 'backOrder')
            ->willReturn($allowBackorders);

        $order = new Order();
        $order->setLineItems(new ArrayCollection($orderLineItems));

        $items = $this->converter->convert($order);

        $this->assertEquals($checkoutLineItems, $items->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function convertDataProvider()
    {
        $parentProduct = $this->getProduct(42, Product::STATUS_ENABLED, Product::INVENTORY_STATUS_IN_STOCK);
        $product1 = $this->getProduct(1, Product::STATUS_DISABLED, Product::INVENTORY_STATUS_IN_STOCK);
        $product2 = $this->getProduct(2, Product::STATUS_ENABLED, Product::INVENTORY_STATUS_OUT_OF_STOCK);
        $product3 = $this->getProduct(3, Product::STATUS_ENABLED, Product::INVENTORY_STATUS_IN_STOCK);

        $productUnit = $this->getEntity(ProductUnit::class, ['code' => 'item']);

        $orderLineItem = $this->getEntity(
            OrderLineItem::class,
            [
                'parentProduct' => $parentProduct,
                'product' => $product3,
                'productUnit' => $productUnit,
                'freeFormProduct' => 'free form product',
                'fromExternalSource' => true,
                'quantity' => 10,
                'price' => Price::create(100, 'USD'),
                'priceType' => 300,
                'comment' => 'test comment',
            ]
        );

        return [
            'can not decrement without quantity' => [
                'orderLineItems' => [
                    $this->getEntity(OrderLineItem::class, ['product' => $product1, 'productUnit' => $productUnit]),
                    $this->getEntity(OrderLineItem::class, ['product' => $product2, 'productUnit' => $productUnit]),
                    $this->getEntity(OrderLineItem::class, ['product' => $product3, 'productUnit' => $productUnit]),
                    $this->getEntity(OrderLineItem::class, [
                        'productSku' => 'MANUAL_PRODUCT',
                        'productUnit' => $productUnit,
                        ]),
                ],
                'canDecrement' => false,
                'availableQuantity' => 10,
                'isVisible' => true,
                'checkoutLineItems' => [],
                'allowBackorders' => false
            ],
            'can decrement with available quantity' => [
                'orderLineItems' => [
                    $this->getEntity(OrderLineItem::class, ['product' => $product1, 'productUnit' => $productUnit]),
                    $this->getEntity(OrderLineItem::class, ['product' => $product2, 'productUnit' => $productUnit]),
                    $orderLineItem
                ],
                'canDecrement' => true,
                'availableQuantity' => 10,
                'isVisible' => true,
                'checkoutLineItems' => [
                    $this->getEntity(
                        CheckoutLineItem::class,
                        [
                            'parentProduct' => $parentProduct,
                            'product' => $product3,
                            'productUnit' => $productUnit,
                            'freeFormProduct' => 'free form product',
                            'fromExternalSource' => false,
                            'quantity' => 10,
                            'comment' => 'test comment',
                        ]
                    ),
                ],
                'allowBackorders' => false
            ],
            'can not decrement with available quantity' => [
                'orderLineItems' => [
                    $this->getEntity(OrderLineItem::class, ['product' => $product1, 'productUnit' => $productUnit]),
                    $this->getEntity(OrderLineItem::class, ['product' => $product2, 'productUnit' => $productUnit]),
                    $orderLineItem
                ],
                'canDecrement' => false,
                'availableQuantity' => 10,
                'isVisible' => true,
                'checkoutLineItems' => [
                    $this->getEntity(
                        CheckoutLineItem::class,
                        [
                            'parentProduct' => $parentProduct,
                            'product' => $product3,
                            'productUnit' => $productUnit,
                            'freeFormProduct' => 'free form product',
                            'fromExternalSource' => false,
                            'quantity' => 10,
                            'comment' => 'test comment',
                        ]
                    ),
                ],
                'allowBackorders' => false
            ],
            'can decrement without available quantity' => [
                'orderLineItems' => [
                    $this->getEntity(OrderLineItem::class, ['product' => $product1, 'productUnit' => $productUnit]),
                    $this->getEntity(OrderLineItem::class, ['product' => $product2, 'productUnit' => $productUnit]),
                    $orderLineItem
                ],
                'canDecrement' => true,
                'availableQuantity' => 0,
                'isVisible' => true,
                'checkoutLineItems' => [],
                'allowBackorders' => false
            ],
            'can decrement without available quantity backorders allowed' => [
                'orderLineItems' => [
                    $this->getEntity(OrderLineItem::class, ['product' => $product1, 'productUnit' => $productUnit]),
                    $this->getEntity(OrderLineItem::class, ['product' => $product2, 'productUnit' => $productUnit]),
                    $orderLineItem
                ],
                'canDecrement' => true,
                'availableQuantity' => 0,
                'isVisible' => true,
                'checkoutLineItems' => [
                    $this->getEntity(
                        CheckoutLineItem::class,
                        [
                            'parentProduct' => $parentProduct,
                            'product' => $product3,
                            'productUnit' => $productUnit,
                            'freeFormProduct' => 'free form product',
                            'fromExternalSource' => false,
                            'quantity' => 10,
                            'comment' => 'test comment',
                        ]
                    ),
                ],
                'allowBackorders' => true
            ],
            'can decrement with less available quantity' => [
                'orderLineItems' => [
                    $this->getEntity(OrderLineItem::class, ['product' => $product1, 'productUnit' => $productUnit]),
                    $this->getEntity(OrderLineItem::class, ['product' => $product2, 'productUnit' => $productUnit]),
                    $orderLineItem
                ],
                'canDecrement' => true,
                'availableQuantity' => 5,
                'isVisible' => true,
                'checkoutLineItems' => [
                    $this->getEntity(
                        CheckoutLineItem::class,
                        [
                            'parentProduct' => $parentProduct,
                            'product' => $product3,
                            'productUnit' => $productUnit,
                            'freeFormProduct' => 'free form product',
                            'fromExternalSource' => false,
                            'quantity' => 5,
                            'comment' => 'test comment',
                        ]
                    ),
                ],
                'allowBackorders' => false
            ],
            'can decrement not visible' => [
                'orderLineItems' => [
                    $this->getEntity(OrderLineItem::class, ['product' => $product1, 'productUnit' => $productUnit]),
                    $this->getEntity(OrderLineItem::class, ['product' => $product2, 'productUnit' => $productUnit]),
                    $orderLineItem
                ],
                'canDecrement' => true,
                'availableQuantity' => 100,
                'isVisible' => false,
                'checkoutLineItems' => [],
                'allowBackorders' => false
            ],
        ];
    }

    /**
     * @param int $id
     * @param string $status
     * @param string $inventoryStatus
     * @return Product|object
     */
    protected function getProduct($id, $status, $inventoryStatus)
    {
        return $this->getEntity(
            Product::class,
            [
                'id' => $id,
                'status' => $status,
                'inventoryStatus' => new TestEnumValue($inventoryStatus, $inventoryStatus)
            ]
        );
    }
}
