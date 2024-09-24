<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Storage;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemLabel;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage as Storage;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Storage\ProductDataStorage;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductDataStorageTest extends TestCase
{
    private Storage|MockObject $storage;

    private ProductDataStorage $productDataStorage;

    #[\Override]
    protected function setUp(): void
    {
        $this->storage = $this->createMock(Storage::class);

        $this->productDataStorage = new ProductDataStorage($this->storage);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSaveToStorage(): void
    {
        $customerId = 10;
        $customerUserId = 42;
        $productSku = 'testSku';
        $productId = 123;
        $quantity = 100;
        $comment = 'Test Comment';
        $unitCode = 'kg';
        $note = 'Note';

        $customer = new Customer();
        ReflectionUtil::setId($customer, $customerId);
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, $customerUserId);

        $product = new Product();
        ReflectionUtil::setId($product, $productId);
        $product->setSku($productSku);

        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        $lineItem = new LineItem();
        $lineItem->setQuantity($quantity);
        $lineItem->setNotes($comment);
        $lineItem->setProduct($product);
        $lineItem->setUnit($productUnit);

        $productKitName = 'Product Kit';
        $productKitSku = 'productKitSku';
        $productKit = (new Product())
            ->setId(2)
            ->setType(Product::TYPE_KIT)
            ->setSku($productKitSku)
            ->setDefaultName($productKitName);
        $productKitUnit = (new ProductUnit())->setCode('item');

        $kitItemProductName = 'Product 3';
        $kitItemProduct = (new Product())->setId(3)->setDefaultName($kitItemProductName);

        $kitItemLabel = 'Kit Item Label';
        $kitItemLabels = [(new ProductKitItemLabel())->setString($kitItemLabel)];
        $kitItem = (new ProductKitItemStub(1))->setLabels($kitItemLabels);
        $kitItemLineItem = $this->createKitItemLineItem(
            1,
            $productKitUnit,
            $kitItemProduct,
            $kitItem
        );

        $kitLineItem = (new LineItem())
            ->setQuantity($quantity)
            ->setNotes($comment)
            ->setProduct($productKit)
            ->setUnit($productKitUnit)
            ->addKitItemLineItem($kitItemLineItem);

        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, 1);
        $shoppingList->setCustomer($customer);
        $shoppingList->setCustomerUser($customerUser);
        $shoppingList->addLineItem($lineItem);
        $shoppingList->addLineItem($kitLineItem);
        $shoppingList->setNotes($note);

        $this->storage->expects($this->once())
            ->method('set')
            ->with(
                [
                    Storage::ENTITY_DATA_KEY => [
                        'customer' => $customerId,
                        'customerUser' => $customerUserId,
                        'sourceEntityId' => 1,
                        'sourceEntityClass' => ClassUtils::getClass($shoppingList),
                        'sourceEntityIdentifier' => 1,
                        'note' => $note
                    ],
                    Storage::ENTITY_ITEMS_DATA_KEY => [
                        [
                            Storage::PRODUCT_SKU_KEY => $productSku,
                            Storage::PRODUCT_ID_KEY => $productId,
                            Storage::PRODUCT_QUANTITY_KEY => $quantity,
                            'comment' => $comment,
                            'productUnit' => $unitCode,
                            'productUnitCode' => $unitCode,
                            'kitItemLineItemsData' => [],
                        ],
                        [
                            Storage::PRODUCT_SKU_KEY => $productKitSku,
                            Storage::PRODUCT_ID_KEY => 2,
                            Storage::PRODUCT_QUANTITY_KEY => $quantity,
                            'comment' => $comment,
                            'productUnit' => 'item',
                            'productUnitCode' => 'item',
                            'kitItemLineItemsData' => [
                                [
                                    'kitItem' => $kitItem->getId(),
                                    'product' => $kitItemProduct->getId(),
                                    'productUnit' => 'item',
                                    'quantity' => 1.0,
                                ],
                            ],
                        ]
                    ]
                ]
            );

        $this->productDataStorage->saveToStorage($shoppingList);
    }

    private function createKitItemLineItem(
        float $quantity,
        ?ProductUnit $productUnit,
        ?Product $product,
        ?ProductKitItem $kitItem
    ): ProductKitItemLineItem {
        return (new ProductKitItemLineItem())
            ->setProduct($product)
            ->setKitItem($kitItem)
            ->setUnit($productUnit)
            ->setQuantity($quantity);
    }
}
