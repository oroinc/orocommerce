<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Storage;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage as Storage;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Storage\ProductDataStorage;
use Oro\Component\Testing\ReflectionUtil;

class ProductDataStorageTest extends \PHPUnit\Framework\TestCase
{
    /** @var Storage|\PHPUnit\Framework\MockObject\MockObject */
    private $storage;

    /** @var ProductDataStorage */
    private $productDataStorage;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(Storage::class);

        $this->productDataStorage = new ProductDataStorage($this->storage);
    }

    public function testSaveToStorage()
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

        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, 1);
        $shoppingList->setCustomer($customer);
        $shoppingList->setCustomerUser($customerUser);
        $shoppingList->addLineItem($lineItem);
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
                        ]
                    ]
                ]
            );

        $this->productDataStorage->saveToStorage($shoppingList);
    }
}
