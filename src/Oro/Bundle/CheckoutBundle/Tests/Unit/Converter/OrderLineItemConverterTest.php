<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Converter\OrderLineItemConverter;
use Oro\Bundle\CheckoutBundle\Converter\ProductKitItemLineItemConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\InventoryBundle\Provider\InventoryQuantityProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderLineItemConverterTest extends TestCase
{
    private const VALIDATION_GROUPS = [['Default', 'order_line_item_to_checkout_line_item_convert']];

    private array $processedValidationGroups = [];

    private InventoryQuantityProviderInterface|MockObject $quantityProvider;

    private EntityFallbackResolver|MockObject $entityFallbackResolver;

    private ValidatorInterface|MockObject $validator;

    private CheckoutValidationGroupsBySourceEntityProvider|MockObject $validationGroupsProvider;

    private OrderLineItemConverter $converter;

    protected function setUp(): void
    {
        $this->quantityProvider = $this->createMock(InventoryQuantityProviderInterface::class);
        $this->entityFallbackResolver = $this->createMock(EntityFallbackResolver::class);
        $this->validationGroupsProvider = $this->createMock(CheckoutValidationGroupsBySourceEntityProvider::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->processedValidationGroups = [new GroupSequence(self::VALIDATION_GROUPS)];

        $this->converter = new OrderLineItemConverter(
            $this->quantityProvider,
            $this->entityFallbackResolver,
            new ProductKitItemLineItemConverter(),
            $this->validator,
            $this->validationGroupsProvider
        );
    }

    /**
     * @dataProvider getIsSourceSupportedDataProvider
     */
    public function testIsSourceSupported($source, bool $isSupported): void
    {
        self::assertEquals($isSupported, $this->converter->isSourceSupported($source));
    }

    public function getIsSourceSupportedDataProvider(): array
    {
        return [
            'Order is supported' => [
                'source' => new Order(),
                'isSupported' => true,
            ],
            'not Order is not supported' => [
                'source' => new \stdClass(),
                'isSupported' => false,
            ],
        ];
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert(
        array $orderLineItems,
        bool $canDecrement,
        int $availableQuantity,
        bool $allowBackorders,
        array $checkoutLineItemsToValidate,
        array $violations,
        array $checkoutLineItems
    ): void {
        $this->quantityProvider->expects(self::any())
            ->method('canDecrement')
            ->willReturnCallback(function (Product $product) use ($canDecrement) {
                return $canDecrement && $product->getId() === 3;
            });

        $this->quantityProvider->expects(self::any())
            ->method('getAvailableQuantity')
            ->willReturnCallback(function (Product $product) use ($availableQuantity) {
                return $product->getId() === 3 ? $availableQuantity : 0;
            });

        $this->entityFallbackResolver->expects(self::any())
            ->method('getFallbackValue')
            ->with($this->isInstanceOf(Product::class), 'backOrder')
            ->willReturn($allowBackorders);

        $this->validationGroupsProvider->expects(self::any())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, OrderLineItem::class)
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::any())
            ->method('validate')
            ->with(new ArrayCollection($checkoutLineItemsToValidate), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList($violations));

        $order = new Order();
        $order->setLineItems(new ArrayCollection($orderLineItems));

        $items = $this->converter->convert($order);

        self::assertEquals($checkoutLineItems, $items->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function convertDataProvider(): array
    {
        $parentProduct = $this->getProduct(42, Product::STATUS_ENABLED, Product::INVENTORY_STATUS_IN_STOCK);
        $product1 = $this->getProduct(1, Product::STATUS_DISABLED, Product::INVENTORY_STATUS_IN_STOCK);
        $product2 = $this->getProduct(2, Product::STATUS_ENABLED, Product::INVENTORY_STATUS_OUT_OF_STOCK);
        $product3 = $this->getProduct(3, Product::STATUS_ENABLED, Product::INVENTORY_STATUS_IN_STOCK);
        $product4 = ($this->getProduct(4, Product::STATUS_ENABLED, Product::INVENTORY_STATUS_IN_STOCK))
            ->setType(Product::TYPE_KIT);

        $productUnit = (new ProductUnit())->setCode('item');

        $freeFormProduct = 'free form product';
        $testComment = 'test comment';

        $orderLineItemProduct3 = (new OrderLineItem())
            ->setParentProduct($parentProduct)
            ->setProduct($product3)
            ->setProductUnit($productUnit)
            ->setFreeFormProduct($freeFormProduct)
            ->setFromExternalSource(true)
            ->setQuantity(10)
            ->setPrice(Price::create(100, 'USD'))
            ->setPriceType(300)
            ->setComment($testComment);

        $orderLineItemProduct1 = (new OrderLineItem())->setProduct($product1)->setProductUnit($productUnit);
        $orderLineItemProduct2 = (new OrderLineItem())->setProduct($product2)->setProductUnit($productUnit);

        $kitItem = new ProductKitItemStub(1);
        $orderKitItemLineItem1 = $this->getOrderProductKitItemLineItem($product1, $kitItem, 1, $productUnit);
        $orderLineItemProductKit = (new OrderLineItem())
            ->setProduct($product4)
            ->setProductUnit($productUnit)
            ->setQuantity(10)
            ->setPrice(Price::create(100, 'USD'))
            ->setPriceType(300)
            ->setChecksum('orderLineItemProductKit')
            ->addKitItemLineItem($orderKitItemLineItem1);

        $checkoutLineItem10Items = (new CheckoutLineItem())
            ->setParentProduct($parentProduct)
            ->setProduct($product3)
            ->setProductUnit($productUnit)
            ->setFreeFormProduct('free form product')
            ->setFromExternalSource(false)
            ->setQuantity(10)
            ->setComment('test comment');

        $checkoutLineItem5Items = (new CheckoutLineItem())
            ->setParentProduct($parentProduct)
            ->setProduct($product3)
            ->setProductUnit($productUnit)
            ->setFreeFormProduct('free form product')
            ->setFromExternalSource(false)
            ->setQuantity(5)
            ->setComment('test comment');

        $checkoutKitItemLineItem1 = $this->getCheckoutProductKitItemLineItem(
            $product1,
            $kitItem,
            $productUnit,
            1
        );
        $checkoutLineItemProductKit = (new CheckoutLineItem())
            ->setProduct($product4)
            ->setProductUnit($productUnit)
            ->setQuantity(10)
            ->setChecksum('orderLineItemProductKit')
            ->addKitItemLineItem($checkoutKitItemLineItem1);

        $violation1 = new ConstraintViolation(
            'Invalid value',
            '',
            [],
            '',
            new PropertyPath('[0].product'),
            ''
        );
        $violation2 = new ConstraintViolation(
            'Invalid value',
            '',
            [],
            '',
            new PropertyPath('[1].kitItemLineItems[0].product'),
            ''
        );

        return [
            'no line items' => [
                'orderLineItems' => [],
                'canDecrement' => false,
                'availableQuantity' => 0,
                'allowBackorders' => false,
                'checkoutLineItemsToValidate' => [],
                'violations' => [],
                'checkoutLineItems' => [],
            ],
            'can not decrement without quantity' => [
                'orderLineItems' => [
                    $orderLineItemProduct1,
                    $orderLineItemProduct2,
                    (new OrderLineItem())->setProduct($product3)->setProductUnit($productUnit),
                    (new OrderLineItem())->setProductSku('MANUAL_PRODUCT')->setProductUnit($productUnit),
                ],
                'canDecrement' => false,
                'availableQuantity' => 10,
                'allowBackorders' => false,
                'checkoutLineItemsToValidate' => [],
                'violations' => [],
                'checkoutLineItems' => [],
            ],
            'can decrement with available quantity' => [
                'orderLineItems' => [
                    $orderLineItemProduct1,
                    $orderLineItemProduct2,
                    $orderLineItemProduct3,
                ],
                'canDecrement' => true,
                'availableQuantity' => 10,
                'allowBackorders' => false,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                ],
                'violations' => [],
                'checkoutLineItems' => [
                    $checkoutLineItem10Items,
                ],
            ],
            'can not decrement with available quantity' => [
                'orderLineItems' => [
                    $orderLineItemProduct1,
                    $orderLineItemProduct2,
                    $orderLineItemProduct3,
                ],
                'canDecrement' => false,
                'availableQuantity' => 10,
                'allowBackorders' => false,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                ],
                'violations' => [],
                'checkoutLineItems' => [
                    $checkoutLineItem10Items,
                ],
            ],
            'can decrement without available quantity' => [
                'orderLineItems' => [
                    $orderLineItemProduct1,
                    $orderLineItemProduct2,
                    $orderLineItemProduct3,
                ],
                'canDecrement' => true,
                'availableQuantity' => 0,
                'allowBackorders' => false,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                ],
                'violations' => [],
                'checkoutLineItems' => [],
            ],
            'can decrement without available quantity backorders allowed' => [
                'orderLineItems' => [
                    $orderLineItemProduct1,
                    $orderLineItemProduct2,
                    $orderLineItemProduct3,
                ],
                'canDecrement' => true,
                'availableQuantity' => 0,
                'allowBackorders' => true,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                ],
                'violations' => [],
                'checkoutLineItems' => [
                    $checkoutLineItem10Items,
                ],
            ],
            'can decrement with less available quantity' => [
                'orderLineItems' => [
                    $orderLineItemProduct1,
                    $orderLineItemProduct2,
                    $orderLineItemProduct3,
                ],
                'canDecrement' => true,
                'availableQuantity' => 5,
                'allowBackorders' => false,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem5Items,
                ],
                'violations' => [],
                'checkoutLineItems' => [
                    $checkoutLineItem5Items,
                ],
            ],
            'can decrement not valid' => [
                'orderLineItems' => [
                    $orderLineItemProduct1,
                    $orderLineItemProduct2,
                    $orderLineItemProduct3,
                ],
                'canDecrement' => true,
                'availableQuantity' => 100,
                'allowBackorders' => false,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                ],
                'violations' => [$violation1],
                'checkoutLineItems' => [],
            ],
            'not valid kit item line item' => [
                'orderLineItems' => [
                    $orderLineItemProduct1,
                    $orderLineItemProduct2,
                    $orderLineItemProduct3,
                    $orderLineItemProductKit,
                ],
                'canDecrement' => true,
                'availableQuantity' => 100,
                'allowBackorders' => false,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                    $checkoutLineItemProductKit,
                ],
                'violations' => [$violation2],
                'checkoutLineItems' => [
                    $checkoutLineItem10Items,
                ],
            ],
            'valid kit item line item' => [
                'orderLineItems' => [
                    $orderLineItemProduct1,
                    $orderLineItemProduct2,
                    $orderLineItemProduct3,
                    $orderLineItemProductKit,
                ],
                'canDecrement' => true,
                'availableQuantity' => 100,
                'allowBackorders' => false,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                    $checkoutLineItemProductKit,
                ],
                'violations' => [],
                'checkoutLineItems' => [
                    $checkoutLineItem10Items,
                    $checkoutLineItemProductKit,
                ],
            ],
        ];
    }

    private function getOrderProductKitItemLineItem(
        ?Product $product,
        ?ProductKitItem $kitItem,
        float $quantity,
        ?ProductUnit $productUnit
    ): OrderProductKitItemLineItem {
        return (new OrderProductKitItemLineItem())
            ->setProduct($product)
            ->setKitItem($kitItem)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setSortOrder(1);
    }

    private function getCheckoutProductKitItemLineItem(
        ?Product $product,
        ?ProductKitItem $kitItem,
        ?ProductUnit $productUnit,
        float $quantity
    ): CheckoutProductKitItemLineItem {
        return (new CheckoutProductKitItemLineItem())
            ->setProduct($product)
            ->setKitItem($kitItem)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setSortOrder(1)
            ->setPriceFixed(false);
    }

    private function getProduct(int $id, string $status, string $inventoryStatus): Product
    {
        return (new Product())
            ->setId($id)
            ->setStatus($status)
            ->setInventoryStatus(new TestEnumValue('test', 'Test', $inventoryStatus));
    }
}
