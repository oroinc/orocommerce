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
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpFoundation\RequestStack;

class QuoteDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $requestStack = $this->createMock(RequestStack::class);
        $this->request = $this->createMock(HttpRequest::class);

        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->entity = new Quote();
        $this->extension = new QuoteDataStorageExtension(
            $requestStack,
            $this->storage,
            $this->doctrineHelper,
            $this->aclHelper,
            $this->productClass
        );
        $this->extension->setDataClass(Quote::class);

        $this->setUpLoggerMock($this->extension);

        $this->initEntityMetadata([
            ProductUnit::class         => [
                'identifier' => ['code'],
            ],
            Quote::class               => [
                'associationMappings' => [
                    'request' => [
                        'targetEntity' => Request::class,
                    ],
                    'customer' => [
                        'targetEntity' => Customer::class,
                    ],
                    'customerUser' => [
                        'targetEntity' => CustomerUser::class,
                    ],
                ],
            ],
            QuoteProductRequest::class => [
                'associationMappings' => [
                    'productUnit' => [
                        'targetEntity' => ProductUnit::class,
                    ],
                    'requestProductItem' => [
                        'targetEntity' => RequestProductItem::class,
                    ],
                ],
            ],
            QuoteProductOffer::class   => [
                'associationMappings' => [
                    'productUnit' => [
                        'targetEntity' => ProductUnit::class,
                    ],
                ],
            ],
        ]);
    }

    /**
     * @dataProvider buildProvider
     */
    public function testBuild(array $inputData)
    {
        /* @var Request $request */
        $request = $this->getEntity(Request::class, $inputData['requestId']);
        /* @var RequestProductItem $requestProductItem */
        $requestProductItem = $this->getEntity(
            RequestProductItem::class,
            $inputData['requestProductItemId']
        );

        $productUnit = (new ProductUnit())->setCode($inputData['productUnitCode']);
        $product = $this->getProductEntity($inputData['productSku'], $productUnit);

        /* @var Customer $customer */
        $customer = $this->getEntity(Customer::class, $inputData['customerId']);
        /* @var CustomerUser $customerUser */
        $customerUser = $this->getEntity(CustomerUser::class, $inputData['customerUserId']);

        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => [
                'request' => $request->getId(),
                'customer' => $customer->getId(),
                'customerUser' => $customerUser->getId(),
            ],
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $inputData['productSku'],
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => null,
                    'commentCustomer' => $inputData['commentCustomer'],
                    'requestProductItems' => [
                        [
                            'price' => $inputData['price'],
                            'quantity' => $inputData['quantity'],
                            'productUnit' => $productUnit->getCode(),
                            'productUnitCode' => $productUnit->getCode(),
                            'requestProductItem' => $requestProductItem->getId(),
                        ]
                    ],
                ],
            ]
        ];

        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        $this->entity = new Quote();

        $this->extension->buildForm($this->getFormBuilder(true), []);

        $this->assertEquals($customer, $this->entity->getCustomer());
        $this->assertEquals($customerUser, $this->entity->getCustomerUser());

        $this->assertCount(1, $this->entity->getQuoteProducts());

        /* @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->entity->getQuoteProducts()->first();

        $this->assertEquals($product, $quoteProduct->getProduct());
        $this->assertEquals($product->getSku(), $quoteProduct->getProductSku());
        $this->assertEquals($inputData['commentCustomer'], $quoteProduct->getCommentCustomer());

        $this->assertCount(1, $quoteProduct->getQuoteProductRequests());
        $this->assertCount(1, $quoteProduct->getQuoteProductOffers());

        /* @var QuoteProductRequest $quoteProductRequest */
        $quoteProductRequest = $quoteProduct->getQuoteProductRequests()->first();

        $this->assertEquals($productUnit, $quoteProductRequest->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $quoteProductRequest->getProductUnitCode());

        $this->assertEquals($inputData['quantity'], $quoteProductRequest->getQuantity());
        $this->assertEquals($inputData['price'], $quoteProductRequest->getPrice());

        /* @var QuoteProductOffer $quoteProductOffer */
        $quoteProductOffer = $quoteProduct->getQuoteProductOffers()->first();

        $this->assertEquals($productUnit, $quoteProductOffer->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $quoteProductOffer->getProductUnitCode());

        $this->assertEquals($inputData['quantity'], $quoteProductOffer->getQuantity());
        $this->assertEquals($inputData['price'], $quoteProductOffer->getPrice());
    }

    public function buildProvider(): array
    {
        return [
            'full data' => [
                'data' => [
                    'requestId' => 1,
                    'requestProductItemId' => 2,
                    'productUnitCode' => 'item',
                    'productSku' => 'TEST SKU',
                    'customerId' => 3,
                    'customerUserId' => 4,
                    'price' => Price::create(5, 'USD'),
                    'quantity' => 6,
                    'commentCustomer' => 'comment 7',
                ],
            ],
        ];
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([QuoteType::class], QuoteDataStorageExtension::getExtendedTypes());
    }
}
