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
use Oro\Component\Testing\Unit\EntityTrait;

class ProductDataStorageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Storage */
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
        $quantity = 100;
        $comment = 'Test Comment';
        $unitCode = 'kg';
        $note = 'Note';

        $customer = $this->getEntity(Customer::class, ['id' => $customerId]);
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => $customerUserId]);

        $product = new Product();
        $product->setSku($productSku);

        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        $lineItem = new LineItem();
        $lineItem
            ->setQuantity($quantity)
            ->setNotes($comment)
            ->setProduct($product)
            ->setUnit($productUnit);

        $shoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 1,
            'customer' => $customer,
            'customerUser' => $customerUser,
            'lineItems' => [$lineItem],
            'notes' => $note,
        ]);

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
