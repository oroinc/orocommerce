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
    protected $storage;

    /** @var ProductDataStorage */
    protected $productDataStorage;

    protected function setUp(): void
    {
        $this->storage = $this->getMockBuilder('Oro\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productDataStorage = new ProductDataStorage($this->storage);
    }

    protected function tearDown(): void
    {
        unset($this->storage, $this->productDataStorage);
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

        /** @var Customer $customer */
        $customer = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', ['id' => $customerId]);

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', ['id' => $customerUserId]);

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

        /** @var ShoppingList $shoppingList */
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
