<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTestCase;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Form\Extension\QuoteDataStorageExtension;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;

class QuoteDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    private Quote $entity;

    protected function setUp(): void
    {
        $this->entity = new Quote();

        parent::setUp();

        $this->extension = new QuoteDataStorageExtension(
            $this->getRequestStack(),
            $this->storage,
            $this->doctrineHelper,
            $this->aclHelper,
            $this->productClass
        );
        $this->extension->setDataClass($this->dataClass);
        $this->setUpLoggerMock($this->extension);

        $this->initEntityMetadata([
            ProductUnit::class         => [
                'identifier' => ['code']
            ],
            Quote::class               => [
                'associationMappings' => [
                    'request' => ['targetEntity' => Request::class],
                    'customer' => ['targetEntity' => Customer::class],
                    'customerUser' => ['targetEntity' => CustomerUser::class]
                ]
            ],
            QuoteProductRequest::class => [
                'associationMappings' => [
                    'productUnit' => ['targetEntity' => ProductUnit::class],
                    'requestProductItem' => ['targetEntity' => RequestProductItem::class]
                ]
            ],
            QuoteProductOffer::class   => [
                'associationMappings' => [
                    'productUnit' => ['targetEntity' => ProductUnit::class]
                ]
            ]
        ]);
    }

    /**
     * {@inheritDoc}
     */
    protected function getTargetEntity(): Quote
    {
        return $this->entity;
    }

    public function testBuildForm(): void
    {
        $requestId = 1;
        $requestProductItemId = 2;
        $productUnitCode = 'item';
        $productId = 123;
        $productSku = 'TEST SKU';
        $customerId = 3;
        $customerUserId = 4;
        $price = Price::create(5, 'USD');
        $quantity = 6;
        $commentCustomer = 'comment 7';

        $request = $this->getEntity(Request::class, $requestId);
        $requestProductItem = $this->getEntity(RequestProductItem::class, $requestProductItemId);

        $productUnit = $this->getProductUnit($productUnitCode);
        $product = $this->getProduct($productSku, $productUnit);

        $customer = $this->getEntity(Customer::class, $customerId);
        $customerUser = $this->getEntity(CustomerUser::class, $customerUserId);

        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => [
                'request' => $request->getId(),
                'customer' => $customer->getId(),
                'customerUser' => $customerUser->getId(),
            ],
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_ID_KEY => $productId,
                    ProductDataStorage::PRODUCT_SKU_KEY => $productSku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => null,
                    'commentCustomer' => $commentCustomer,
                    'requestProductItems' => [
                        [
                            'price' => $price,
                            'quantity' => $quantity,
                            'productUnit' => $productUnit->getCode(),
                            'productUnitCode' => $productUnit->getCode(),
                            'requestProductItem' => $requestProductItem->getId(),
                        ]
                    ]
                ]
            ]
        ];

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsFindProduct($productId, $product);

        $this->extension->buildForm($this->getFormBuilder(), []);

        $this->assertEquals($customer, $this->entity->getCustomer());
        $this->assertEquals($customerUser, $this->entity->getCustomerUser());

        $this->assertCount(1, $this->entity->getQuoteProducts());

        /* @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->entity->getQuoteProducts()->first();

        $this->assertEquals($product, $quoteProduct->getProduct());
        $this->assertEquals($product->getSku(), $quoteProduct->getProductSku());
        $this->assertEquals($commentCustomer, $quoteProduct->getCommentCustomer());

        $this->assertCount(1, $quoteProduct->getQuoteProductRequests());
        $this->assertCount(1, $quoteProduct->getQuoteProductOffers());

        /* @var QuoteProductRequest $quoteProductRequest */
        $quoteProductRequest = $quoteProduct->getQuoteProductRequests()->first();

        $this->assertEquals($productUnit, $quoteProductRequest->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $quoteProductRequest->getProductUnitCode());

        $this->assertEquals($quantity, $quoteProductRequest->getQuantity());
        $this->assertEquals($price, $quoteProductRequest->getPrice());

        /* @var QuoteProductOffer $quoteProductOffer */
        $quoteProductOffer = $quoteProduct->getQuoteProductOffers()->first();

        $this->assertEquals($productUnit, $quoteProductOffer->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $quoteProductOffer->getProductUnitCode());

        $this->assertEquals($quantity, $quoteProductOffer->getQuantity());
        $this->assertEquals($price, $quoteProductOffer->getPrice());
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([QuoteType::class], QuoteDataStorageExtension::getExtendedTypes());
    }
}
