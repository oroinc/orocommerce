<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Storage;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Storage\RequestToQuoteDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;

class RequestToQuoteDataStorageTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductDataStorage */
    protected $storage;

    /** @var RequestToQuoteDataStorage */
    protected $requestDataStorage;

    protected function setUp()
    {
        $this->storage = $this->getMockBuilder('Oro\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestDataStorage = new RequestToQuoteDataStorage($this->storage);
    }

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

        $rfpRequest = $this->createRFPRequest($accountId, $accountUserId, $productSku, $unitCode, $quantity, $comment);

        /** @var RequestProduct $requestProduct */
        $requestProduct = $rfpRequest->getRequestProducts()->first();

        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $requestProduct->getRequestProductItems()->first();

        $this->storage->expects($this->once())
            ->method('set')
            ->with([
                ProductDataStorage::ENTITY_DATA_KEY => [
                    'account' => $accountId,
                    'accountUser' => $accountUserId,
                    'request' => null,
                    'poNumber' => null,
                    'shipUntil' => null
                ],
                ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => $productSku,
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => null,
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

    /**
     * @param int $accountId
     * @param int $accountUserId
     * @param string $productSku
     * @param string $unitCode
     * @param float $quantity
     * @param string $comment
     * @return RFPRequest
     */
    protected function createRFPRequest($accountId, $accountUserId, $productSku, $unitCode, $quantity, $comment)
    {
        /** @var Account $account */
        $account = $this->getEntity('Oro\Bundle\AccountBundle\Entity\Account', ['id' => $accountId]);

        /** @var AccountUser $accountUser */
        $accountUser = $this->getEntity('Oro\Bundle\AccountBundle\Entity\AccountUser', ['id' => $accountUserId]);

        $product = new Product();
        $product->setSku($productSku);

        $productUnit = new ProductUnit();
        $productUnit->setCode($unitCode);

        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $this->getEntity(
            'Oro\Bundle\RFPBundle\Entity\RequestProductItem',
            [
                'id' => rand(1, 1000),
                'quantity' => $quantity,
                'productUnit' => $productUnit
            ]
        );

        $requestProduct = new RequestProduct();
        $requestProduct
            ->setProduct($product)
            ->setComment($comment)
            ->addRequestProductItem($requestProductItem);

        $rfpRequest = new RFPRequest();
        $rfpRequest
            ->setAccount($account)
            ->setAccountUser($accountUser)
            ->addRequestProduct($requestProduct);

        return $rfpRequest;
    }
}
