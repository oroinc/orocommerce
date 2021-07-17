<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\Product;
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
use Symfony\Component\HttpFoundation\RequestStack;

class QuoteDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        /* @var $requestStack RequestStack|\PHPUnit\Framework\MockObject\MockObject */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');

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
        $this->extension->setDataClass('Oro\Bundle\SaleBundle\Entity\Quote');

        $this->setUpLoggerMock($this->extension);

        $this->initEntityMetadata([
            'Oro\Bundle\ProductBundle\Entity\ProductUnit' => [
                'identifier' => ['code'],
            ],
            'Oro\Bundle\SaleBundle\Entity\Quote' => [
                'associationMappings' => [
                    'request' => [
                        'targetEntity' => 'Oro\Bundle\RFPBundle\Entity\Request',
                    ],
                    'customer' => [
                        'targetEntity' => 'Oro\Bundle\CustomerBundle\Entity\Customer',
                    ],
                    'customerUser' => [
                        'targetEntity' => 'Oro\Bundle\CustomerBundle\Entity\CustomerUser',
                    ],
                ],
            ],
            'Oro\Bundle\SaleBundle\Entity\QuoteProductRequest' => [
                'associationMappings' => [
                    'productUnit' => [
                        'targetEntity' => 'Oro\Bundle\ProductBundle\Entity\ProductUnit',
                    ],
                    'requestProductItem' => [
                        'targetEntity' => 'Oro\Bundle\RFPBundle\Entity\RequestProductItem',
                    ],
                ],
            ],
            'Oro\Bundle\SaleBundle\Entity\QuoteProductOffer' => [
                'associationMappings' => [
                    'productUnit' => [
                        'targetEntity' => 'Oro\Bundle\ProductBundle\Entity\ProductUnit',
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
        /* @var $request Request */
        $request = $this->getEntity('Oro\Bundle\RFPBundle\Entity\Request', $inputData['requestId']);
        /* @var $requestProductItem RequestProductItem */
        $requestProductItem = $this->getEntity(
            'Oro\Bundle\RFPBundle\Entity\RequestProductItem',
            $inputData['requestProductItemId']
        );

        $productUnit = (new ProductUnit())->setCode($inputData['productUnitCode']);
        /* @var $product Product */
        $product = $this->getProductEntity($inputData['productSku'], $productUnit);

        /* @var $customer Customer */
        $customer = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', $inputData['customerId']);
        /* @var $customerUser CustomerUser */
        $customerUser = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\CustomerUser', $inputData['customerUserId']);

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

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertEquals($customer, $this->entity->getCustomer());
        $this->assertEquals($customerUser, $this->entity->getCustomerUser());

        $this->assertCount(1, $this->entity->getQuoteProducts());

        /* @var $quoteProduct QuoteProduct */
        $quoteProduct = $this->entity->getQuoteProducts()->first();

        $this->assertEquals($product, $quoteProduct->getProduct());
        $this->assertEquals($product->getSku(), $quoteProduct->getProductSku());
        $this->assertEquals($inputData['commentCustomer'], $quoteProduct->getCommentCustomer());

        $this->assertCount(1, $quoteProduct->getQuoteProductRequests());
        $this->assertCount(1, $quoteProduct->getQuoteProductOffers());

        /* @var $quoteProductRequest QuoteProductRequest */
        $quoteProductRequest = $quoteProduct->getQuoteProductRequests()->first();

        $this->assertEquals($productUnit, $quoteProductRequest->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $quoteProductRequest->getProductUnitCode());

        $this->assertEquals($inputData['quantity'], $quoteProductRequest->getQuantity());
        $this->assertEquals($inputData['price'], $quoteProductRequest->getPrice());

        /* @var $quoteProductOffer QuoteProductOffer */
        $quoteProductOffer = $quoteProduct->getQuoteProductOffers()->first();

        $this->assertEquals($productUnit, $quoteProductOffer->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $quoteProductOffer->getProductUnitCode());

        $this->assertEquals($inputData['quantity'], $quoteProductOffer->getQuantity());
        $this->assertEquals($inputData['price'], $quoteProductOffer->getPrice());
    }

    /**
     * @return array
     */
    public function buildProvider()
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
