<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Storage;

use Doctrine\Common\Util\ClassUtils;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage as Storage;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Storage\ProductDataStorage;

class ProductDataStorageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Storage */
    protected $storage;

    /** @var ProductDataStorage */
    protected $productDataStorage;

    protected function setUp()
    {
        $this->storage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productDataStorage = new ProductDataStorage($this->storage);
    }

    protected function tearDown()
    {
        unset($this->storage, $this->productDataStorage);
    }

    public function testSaveToStorage()
    {
        $accountId = 10;
        $accountUserId = 42;
        $productSku = 'testSku';
        $quantity = 100;
        $comment = 'Test Comment';
        $unitCode = 'kg';

        /** @var Account $account */
        $account = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\Account', ['id' => $accountId]);

        /** @var AccountUser $accountUser */
        $accountUser = $this->getEntity('OroB2B\Bundle\AccountBundle\Entity\AccountUser', ['id' => $accountUserId]);

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

        $shoppingList = new ShoppingList();

        $this->setId($shoppingList, 1);
        $shoppingList
            ->setAccount($account)
            ->setAccountUser($accountUser)
            ->addLineItem($lineItem);

        $this->storage->expects($this->once())
            ->method('set')
            ->with(
                [
                    Storage::ENTITY_DATA_KEY => [
                        'account' => $accountId,
                        'accountUser' => $accountUserId,
                        'sourceEntityId' => 1,
                        'sourceEntityClass' => ClassUtils::getClass($shoppingList),
                        'sourceEntityIdentifier' => 1
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

    /**
     * @param mixed $obj
     * @param mixed $val
     */
    protected function setId($obj, $val)
    {
        $class = new \ReflectionClass($obj);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($obj, $val);
    }
}
