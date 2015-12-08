<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Storage;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage as Storage;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPBundle\Storage\RequestDataStorage;
use OroB2B\Bundle\RFPBundle\Entity\Request as RFPRequest;

class RequestDataStorageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Storage */
    protected $storage;

    /** @var RequestDataStorage */
    protected $requestDataStorage;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->storage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestDataStorage = new RequestDataStorage($this->storage);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->storage, $this->requestDataStorage);
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

        $requestProductItem = new RequestProductItem();
        $requestProductItem
            ->setQuantity($quantity)
            ->setProductUnit($productUnit);

        $requestProduct = new RequestProduct();

        $requestProduct
            ->setProduct($product)
            ->setComment($comment)
            ->addRequestProductItem($requestProductItem)
        ;

        $rfpRequest = new RFPRequest();
        $rfpRequest
            ->setAccount($account)
            ->setAccountUser($accountUser)
            ->addRequestProduct($requestProduct);

        $this->storage->expects($this->once())
            ->method('set')
            ->with([
                Storage::ENTITY_DATA_KEY => [
                    'account' => $accountId,
                    'accountUser' => $accountUserId,
                    'request' => null,
                    'poNumber' => null,
                    'shipUntil' => null
                ],
                Storage::ENTITY_ITEMS_DATA_KEY => [
                    [
                        Storage::PRODUCT_SKU_KEY => $productSku,
                        Storage::PRODUCT_QUANTITY_KEY => null,
                        'commentAccount' => $comment,
                        'requestProductItems' => [
                            [
                                'productUnit' => $unitCode,
                                'productUnitCode' => $unitCode,
                                'requestProductItem' => $requestProductItem->getId(),
                                'quantity' => $quantity,
                                'price' => null,
                            ],
                        ]
                    ]
                ]
            ]);

        $this->requestDataStorage->saveToStorage($rfpRequest);
    }
}
