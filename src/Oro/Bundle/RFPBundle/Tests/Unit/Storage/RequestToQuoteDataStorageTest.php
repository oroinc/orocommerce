<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Storage;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Storage\RequestToQuoteDataStorage;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * Unit tests for RequestToQuoteDataStorage
 */
class RequestToQuoteDataStorageTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ProductDataStorage|\PHPUnit\Framework\MockObject\MockObject
     */
    private $storage;

    /**
     * @var RequestToQuoteDataStorage
     */
    private $requestDataStorage;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(ProductDataStorage::class);
        $this->requestDataStorage = new RequestToQuoteDataStorage($this->storage);
    }

    /**
     * @dataProvider saveToStorageDataProvider
     */
    public function testSaveToStorage(
        array $rfpRequestData,
        array $entityData,
        array $entityItemData
    ) {
        $rfpRequest = $this->createRFPRequest($rfpRequestData);
        $rfpRequest->addRequestProduct($this->createRequestProduct($rfpRequestData['requestProductData']));
        $rfpRequest->addRequestProduct($this->createRequestProduct($rfpRequestData['requestProductData']));

        $entityItemData['requestProductItems'][0]['price'] = $rfpRequestData['requestProductData']['price'];

        $this->storage
            ->expects(self::once())
            ->method('set')
            ->with([
                ProductDataStorage::ENTITY_DATA_KEY => $entityData,
                ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [$entityItemData, $entityItemData],
            ]);

        $this->requestDataStorage->saveToStorage($rfpRequest);
    }

    public function saveToStorageDataProvider(): array
    {
        $rfpRequestData = [
            'customerId' => 10,
            'customerUserId' => 42,
            'assignedUsers' => [1, 3, 7],
            'assignedCustomerUsers' => [2, 5],
            'website' => 1,
            'requestProductData' => [
                'productId' => 1,
                'productSku' => 'testSku',
                'quantity' => 100,
                'comment' => 'Test Comment',
                'unitCode' => 'kg',
                'price' => Price::create('99', 'USD'),
            ],
        ];

        $entityData = $this->getExpectedEntityData($rfpRequestData);
        $entityItemData = $this->getExpectedEntityItemData($rfpRequestData);

        return [
            [
                'rfpRequestData' => $rfpRequestData,
                'entityData' => $entityData,
                'entityItemData' => $entityItemData,
            ]
        ];
    }

    /**
     * @dataProvider saveToStorageDataProviderWhenNoTargetPrice
     */
    public function testSaveToStorageWhenNoTargetPriceSet(
        array $rfpRequestData,
        array $entityData,
        array $entityItemData
    ) {
        $rfpRequest = $this->createRFPRequest($rfpRequestData);
        $rfpRequest->addRequestProduct($this->createRequestProduct($rfpRequestData['requestProductData']));

        $entityItemData['requestProductItems'][0]['price'] = null;

        $this->storage
            ->expects(self::once())
            ->method('set')
            ->with([
                ProductDataStorage::ENTITY_DATA_KEY => $entityData,
                ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [$entityItemData],
            ]);

        $this->requestDataStorage->saveToStorage($rfpRequest);
    }

    public function saveToStorageDataProviderWhenNoTargetPrice(): array
    {
        $rfpRequestData = [
            'customerId' => 10,
            'customerUserId' => 42,
            'assignedUsers' => [1, 3, 7],
            'assignedCustomerUsers' => [2, 5],
            'website' => 1,
            'requestProductData' => [
                'productId' => 1,
                'productSku' => 'testSku',
                'quantity' => 100,
                'comment' => 'Test Comment',
                'unitCode' => 'kg',
                'price' => null,
            ],
        ];

        $entityData = $this->getExpectedEntityData($rfpRequestData);
        $entityItemData = $this->getExpectedEntityItemData($rfpRequestData);

        return [
            [
                'rfpRequestData' => $rfpRequestData,
                'entityData' => $entityData,
                'entityItemData' => $entityItemData
            ]
        ];
    }

    protected function createRFPRequest(array $rfpRequestData): RFPRequest
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(
            Customer::class,
            ['id' => $rfpRequestData['customerId']]
        );

        /** @var CustomerUser $customerUser */
        $customerUser = $this->getEntity(
            CustomerUser::class,
            ['id' => $rfpRequestData['customerUserId']]
        );

        $website = $this->getEntity(Website::class, ['id' => $rfpRequestData['website']]);

        $rfpRequest = new RFPRequest();
        $rfpRequest
            ->setCustomer($customer)
            ->setCustomerUser($customerUser)
            ->setWebsite($website);

        foreach ($rfpRequestData['assignedUsers'] as $assignedUserId) {
            /** @var \Oro\Bundle\UserBundle\Entity\User $assignedUser */
            $assignedUser = $this->getEntity(
                User::class,
                ['id' => $assignedUserId]
            );
            $rfpRequest->addAssignedUser($assignedUser);
        }

        foreach ($rfpRequestData['assignedCustomerUsers'] as $assignedCustomerUserId) {
            /** @var \Oro\Bundle\CustomerBundle\Entity\CustomerUser $assignedCustomerUser */
            $assignedCustomerUser = $this->getEntity(
                CustomerUser::class,
                ['id' => $assignedCustomerUserId]
            );
            $rfpRequest->addAssignedCustomerUser($assignedCustomerUser);
        }

        return $rfpRequest;
    }

    private function createRequestProduct(array $requestProductData): RequestProduct
    {
        $product = $this->getEntity(
            Product::class,
            [
                'id' => $requestProductData['productId'],
                'sku' => $requestProductData['productSku']
            ]
        );

        $productUnit = new ProductUnit();
        $productUnit->setCode($requestProductData['unitCode']);

        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $this->getEntity(
            RequestProductItem::class,
            [
                'id' => 1,
                'quantity' => $requestProductData['quantity'],
                'productUnit' => $productUnit,
                'price' => $requestProductData['price'],
            ]
        );

        $requestProduct = new RequestProduct();
        $requestProduct
            ->setProduct($product)
            ->setComment($requestProductData['comment'])
            ->addRequestProductItem($requestProductItem);

        return $requestProduct;
    }

    private function getExpectedEntityData($rfpRequestData): array
    {
        $entityData = [
            'customer' => $rfpRequestData['customerId'],
            'customerUser' => $rfpRequestData['customerUserId'],
            'request' => null,
            'poNumber' => null,
            'shipUntil' => null,
            'assignedUsers' => $rfpRequestData['assignedUsers'],
            'assignedCustomerUsers' => $rfpRequestData['assignedCustomerUsers'],
            'website' => 1,
        ];
        return $entityData;
    }

    private function getExpectedEntityItemData(array $rfpRequestData): array
    {
        $entityItemData = [
            ProductDataStorage::PRODUCT_SKU_KEY => $rfpRequestData['requestProductData']['productSku'],
            ProductDataStorage::PRODUCT_QUANTITY_KEY => null,
            'commentCustomer' => $rfpRequestData['requestProductData']['comment'],
            'requestProductItems' => [
                [
                    'productUnit' => $rfpRequestData['requestProductData']['unitCode'],
                    'productUnitCode' => $rfpRequestData['requestProductData']['unitCode'],
                    'requestProductItem' => 1,
                    'quantity' => $rfpRequestData['requestProductData']['quantity'],
                ],
            ]
        ];
        return $entityItemData;
    }
}
