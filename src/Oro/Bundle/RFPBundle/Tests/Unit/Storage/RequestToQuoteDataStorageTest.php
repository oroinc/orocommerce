<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Storage;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
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
        $rfpRequestData = [
            'customerId' => 10,
            'customerUserId' => 42,
            'productSku' => 'testSku',
            'quantity' => 100,
            'comment' => 'Test Comment',
            'unitCode' => 'kg',
            'assignedUsers' => [1, 3, 7],
            'assignedCustomerUsers' => [2, 5],
        ];

        $rfpRequest = $this->createRFPRequest($rfpRequestData);

        /** @var RequestProduct $requestProduct */
        $requestProduct = $rfpRequest->getRequestProducts()->first();

        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $requestProduct->getRequestProductItems()->first();

        $this->storage->expects($this->once())
            ->method('set')
            ->with([
                ProductDataStorage::ENTITY_DATA_KEY => [
                    'customer' => $rfpRequestData['customerId'],
                    'customerUser' => $rfpRequestData['customerUserId'],
                    'request' => null,
                    'poNumber' => null,
                    'shipUntil' => null,
                    'assignedUsers' => [1, 3, 7],
                    'assignedCustomerUsers' => [2, 5],
                ],
                ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                    [
                        ProductDataStorage::PRODUCT_SKU_KEY => $rfpRequestData['productSku'],
                        ProductDataStorage::PRODUCT_QUANTITY_KEY => null,
                        'commentCustomer' => $rfpRequestData['comment'],
                        'requestProductItems' => [
                            [
                                'productUnit' => $rfpRequestData['unitCode'],
                                'productUnitCode' => $rfpRequestData['unitCode'],
                                'requestProductItem' => $requestProductItem->getId(),
                                'quantity' => $rfpRequestData['quantity'],
                                'price' => null,
                            ],
                        ]
                    ]
                ]
            ]);

        $this->requestDataStorage->saveToStorage($rfpRequest);
    }

    /**
     * @param array $rfpRequestData
     * @return RFPRequest
     */
    protected function createRFPRequest($rfpRequestData)
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(
            'Oro\Bundle\CustomerBundle\Entity\Customer',
            ['id' => $rfpRequestData['customerId']]
        );

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntity(
            'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
            ['id' => $rfpRequestData['customerUserId']]
        );

        $product = new Product();
        $product->setSku($rfpRequestData['productSku']);

        $productUnit = new ProductUnit();
        $productUnit->setCode($rfpRequestData['unitCode']);

        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $this->getEntity(
            'Oro\Bundle\RFPBundle\Entity\RequestProductItem',
            [
                'id' => rand(1, 1000),
                'quantity' => $rfpRequestData['quantity'],
                'productUnit' => $productUnit
            ]
        );

        $requestProduct = new RequestProduct();
        $requestProduct
            ->setProduct($product)
            ->setComment($rfpRequestData['comment'])
            ->addRequestProductItem($requestProductItem);

        $rfpRequest = new RFPRequest();
        $rfpRequest
            ->setCustomer($customer)
            ->setCustomerUser($customerUser)
            ->addRequestProduct($requestProduct);

        foreach ($rfpRequestData['assignedUsers'] as $assignedUserId) {
            /** @var \Oro\Bundle\UserBundle\Entity\User $assignedUser */
            $assignedUser = $this->getEntity(
                'Oro\Bundle\UserBundle\Entity\User',
                ['id' => $assignedUserId]
            );
            $rfpRequest->addAssignedUser($assignedUser);
        }

        foreach ($rfpRequestData['assignedCustomerUsers'] as $assignedCustomerUserId) {
            /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUser $assignedCustomerUser */
            $assignedCustomerUser = $this->getEntity(
                'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
                ['id' => $assignedCustomerUserId]
            );
            $rfpRequest->addAssignedCustomerUser($assignedCustomerUser);
        }

        return $rfpRequest;
    }
}
