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
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Storage\RequestToQuoteDataStorage;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestToQuoteDataStorageTest extends TestCase
{
    private ProductDataStorage&MockObject $storage;
    private RequestToQuoteDataStorage $requestDataStorage;

    #[\Override]
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
    ): void {
        $rfpRequest = $this->createRFPRequest($rfpRequestData);
        $rfpRequest->addRequestProduct($this->createRequestProduct($rfpRequestData['requestProductData']));
        $rfpRequest->addRequestProduct($this->createRequestProduct($rfpRequestData['requestProductData']));

        $entityItemData['requestProductItems'][0]['price'] = $rfpRequestData['requestProductData']['price'];

        $this->storage->expects(self::once())
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
            'projectName' => 'Test Project',
            'requestProductData' => [
                'productId' => 1,
                'productSku' => 'testSku',
                'quantity' => 100,
                'comment' => 'Test Comment',
                'unitCode' => 'kg',
                'price' => Price::create('99', 'USD'),
                'kitItemLineItemsData' => [],
            ],
        ];

        $entityData = $this->getExpectedEntityData($rfpRequestData);
        $entityItemData = $this->getExpectedEntityItemData($rfpRequestData);

        $rfpKitRequestData = array_merge_recursive(
            [
                'requestProductData' => [
                    'kitItemLineItemsData' => [
                        [
                            'kitItemId' => 1,
                            'kitItemLabel' => 'Base Unit',
                            'optional' => false,
                            'minimumQuantity' => 1,
                            'maximumQuantity' => 2,
                            'productId' => 2,
                            'productName' => 'Product 2',
                            'productSku' => 'SKUPRODUCT2',
                            'productUnitCode' => 'kg',
                            'productUnitPrecision' => 0,
                            'quantity' => 2,
                            'sortOrder' => 1,
                        ],
                    ],
                ],
            ],
            $rfpRequestData
        );

        $kitEntityItemData = $this->getExpectedEntityItemData($rfpKitRequestData);

        return [
            'simple product' => [
                'rfpRequestData' => $rfpRequestData,
                'entityData' => $entityData,
                'entityItemData' => $entityItemData,
            ],
            'product kit' => [
                'rfpRequestData' => $rfpKitRequestData,
                'entityData' => $entityData,
                'entityItemData' => $kitEntityItemData,
            ],
        ];
    }

    /**
     * @dataProvider saveToStorageDataProviderWhenNoTargetPrice
     */
    public function testSaveToStorageWhenNoTargetPriceSet(
        array $rfpRequestData,
        array $entityData,
        array $entityItemData
    ): void {
        $rfpRequest = $this->createRFPRequest($rfpRequestData);
        $rfpRequest->addRequestProduct($this->createRequestProduct($rfpRequestData['requestProductData']));

        $entityItemData['requestProductItems'][0]['price'] = null;

        $this->storage->expects(self::once())
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
                'kitItemLineItemsData' => [],
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

    private function createRFPRequest(array $rfpRequestData): RFPRequest
    {
        $customer = new Customer();
        ReflectionUtil::setId($customer, $rfpRequestData['customerId']);
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, $rfpRequestData['customerUserId']);
        $website = new Website();
        ReflectionUtil::setId($website, $rfpRequestData['website']);

        $rfpRequest = new RFPRequest();
        $rfpRequest->setProjectName('Test Project');
        $rfpRequest->setCustomer($customer);
        $rfpRequest->setCustomerUser($customerUser);
        $rfpRequest->setWebsite($website);

        foreach ($rfpRequestData['assignedUsers'] as $assignedUserId) {
            $assignedUser = new User();
            ReflectionUtil::setId($assignedUser, $assignedUserId);
            $rfpRequest->addAssignedUser($assignedUser);
        }

        foreach ($rfpRequestData['assignedCustomerUsers'] as $assignedCustomerUserId) {
            $assignedCustomerUser = new CustomerUser();
            ReflectionUtil::setId($assignedCustomerUser, $assignedCustomerUserId);
            $rfpRequest->addAssignedCustomerUser($assignedCustomerUser);
        }

        return $rfpRequest;
    }

    private function createRequestProduct(array $requestProductData): RequestProduct
    {
        $product = new Product();
        ReflectionUtil::setId($product, $requestProductData['productId']);
        $product->setSku($requestProductData['productSku']);

        $productUnit = new ProductUnit();
        $productUnit->setCode($requestProductData['unitCode']);

        $requestProductItem = new RequestProductItem();
        ReflectionUtil::setId($requestProductItem, 1);
        $requestProductItem->setQuantity($requestProductData['quantity']);
        $requestProductItem->setProductUnit($productUnit);
        $requestProductItem->setPrice($requestProductData['price']);

        $requestProduct = new RequestProduct();
        $requestProduct->setProduct($product);
        $requestProduct->setComment($requestProductData['comment']);
        $requestProduct->addRequestProductItem($requestProductItem);

        foreach ($requestProductData['kitItemLineItemsData'] as $kitItemLineItemData) {
            $kitItemProductUnit = new ProductUnit();
            $kitItemProductUnit->setCode($kitItemLineItemData['productUnitCode']);
            $kitItemProductUnit->setDefaultPrecision($kitItemLineItemData['productUnitPrecision']);

            $requestProductKitItemLineItem = (new RequestProductKitItemLineItem())
                ->setProductId($kitItemLineItemData['productId'])
                ->setProductSku($kitItemLineItemData['productSku'])
                ->setProductName($kitItemLineItemData['productName'])
                ->setProductUnit($kitItemProductUnit)
                ->setKitItemId($kitItemLineItemData['kitItemId'])
                ->setKitItemLabel($kitItemLineItemData['kitItemLabel'])
                ->setMinimumQuantity($kitItemLineItemData['minimumQuantity'])
                ->setMaximumQuantity($kitItemLineItemData['maximumQuantity'])
                ->setOptional($kitItemLineItemData['optional'])
                ->setQuantity($kitItemLineItemData['quantity'])
                ->setSortOrder($kitItemLineItemData['sortOrder']);

            $requestProduct->addKitItemLineItem($requestProductKitItemLineItem);
        }

        return $requestProduct;
    }

    private function getExpectedEntityData($rfpRequestData): array
    {
        return [
            'customer' => $rfpRequestData['customerId'],
            'customerUser' => $rfpRequestData['customerUserId'],
            'request' => null,
            'projectName' => 'Test Project',
            'poNumber' => null,
            'shipUntil' => null,
            'assignedUsers' => $rfpRequestData['assignedUsers'],
            'assignedCustomerUsers' => $rfpRequestData['assignedCustomerUsers'],
            'website' => 1,
            'visitor' => null
        ];
    }

    private function getExpectedEntityItemData(array $rfpRequestData): array
    {
        return [
            ProductDataStorage::PRODUCT_SKU_KEY => $rfpRequestData['requestProductData']['productSku'],
            ProductDataStorage::PRODUCT_ID_KEY => $rfpRequestData['requestProductData']['productId'],
            ProductDataStorage::PRODUCT_QUANTITY_KEY => null,
            'commentCustomer' => $rfpRequestData['requestProductData']['comment'],
            'requestProductItems' => [
                [
                    'productUnit' => $rfpRequestData['requestProductData']['unitCode'],
                    'productUnitCode' => $rfpRequestData['requestProductData']['unitCode'],
                    'requestProductItem' => 1,
                    'quantity' => $rfpRequestData['requestProductData']['quantity'],
                ],
            ],
            ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEMS_DATA_KEY => $this->getExpectedKitItemLineItemsData(
                $rfpRequestData['requestProductData']['kitItemLineItemsData']
            )
        ];
    }

    private function getExpectedKitItemLineItemsData(array $kitItemLineItems): array
    {
        $kitItemLineItemsData = [];
        foreach ($kitItemLineItems as $kitItemLineItem) {
            $kitItemLineItemsData[] = [
                'kitItem' => $kitItemLineItem['kitItem'] ?? null,
                'kitItemId' => $kitItemLineItem['kitItemId'],
                'kitItemLabel' => $kitItemLineItem['kitItemLabel'],
                'optional' => $kitItemLineItem['optional'],
                'minimumQuantity' => (float)$kitItemLineItem['minimumQuantity'],
                'maximumQuantity' => (float)$kitItemLineItem['maximumQuantity'],
                'product' => $kitItemLineItem['product'] ?? null,
                'productId' => $kitItemLineItem['productId'],
                'productName' => $kitItemLineItem['productName'],
                'productSku' => $kitItemLineItem['productSku'],
                'productUnit' => $kitItemLineItem['productUnitCode'],
                'productUnitCode' => $kitItemLineItem['productUnitCode'],
                'productUnitPrecision' => $kitItemLineItem['productUnitPrecision'],
                'quantity' => $kitItemLineItem['quantity'],
                'sortOrder' => $kitItemLineItem['sortOrder'],
            ];
        }

        return $kitItemLineItemsData;
    }
}
