<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTestCase;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Form\Extension\QuoteDataStorageExtension;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\Model\BaseQuoteProductItem;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPricesProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class QuoteDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    private QuoteProductPricesProvider&MockObject $quoteProductPricesProvider;
    private Quote $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->quoteProductPricesProvider = $this->createMock(QuoteProductPricesProvider::class);
        $this->entity = new Quote();

        parent::setUp();

        $this->initEntityMetadata([
            ProductUnit::class => [
                'identifier' => ['code'],
            ],
            Quote::class => [
                'associationMappings' => [
                    'request' => ['targetEntity' => Request::class],
                    'customer' => ['targetEntity' => Customer::class],
                    'customerUser' => ['targetEntity' => CustomerUser::class],
                ],
            ],
            QuoteProductRequest::class => [
                'associationMappings' => [
                    'productUnit' => ['targetEntity' => ProductUnit::class],
                    'requestProductItem' => ['targetEntity' => RequestProductItem::class],
                ],
            ],
            QuoteProductOffer::class => [
                'associationMappings' => [
                    'productUnit' => ['targetEntity' => ProductUnit::class],
                ],
            ],
        ]);
    }

    #[\Override]
    protected function getExtension(): AbstractProductDataStorageExtension
    {
        $lineItemChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);
        $lineItemChecksumGenerator->expects(self::any())
            ->method('getChecksum')
            ->willReturnCallback(static function (BaseQuoteProductItem $quoteProductItem) {
                return ($quoteProductItem->getProduct()?->getId()
                    .'|'.$quoteProductItem->getProductUnit()?->getCode()
                    .'|'.$quoteProductItem->getQuantity());
            });

        return new QuoteDataStorageExtension(
            $this->getRequestStack(),
            $this->storage,
            PropertyAccess::createPropertyAccessor(),
            $this->doctrine,
            $lineItemChecksumGenerator,
            $this->logger,
            $this->quoteProductPricesProvider
        );
    }

    #[\Override]
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
                        ],
                    ],
                ],
            ],
        ];

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsFindProduct($productId, $product);

        $this->getExtension()->buildForm($this->getFormBuilder(), []);

        self::assertEquals($customer, $this->entity->getCustomer());
        self::assertEquals($customerUser, $this->entity->getCustomerUser());

        self::assertCount(1, $this->entity->getQuoteProducts());

        /* @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->entity->getQuoteProducts()->first();

        self::assertEquals($product, $quoteProduct->getProduct());
        self::assertEquals($product->getSku(), $quoteProduct->getProductSku());
        self::assertEquals($commentCustomer, $quoteProduct->getCommentCustomer());

        self::assertCount(1, $quoteProduct->getQuoteProductRequests());
        self::assertCount(1, $quoteProduct->getQuoteProductOffers());

        /* @var QuoteProductRequest $quoteProductRequest */
        $quoteProductRequest = $quoteProduct->getQuoteProductRequests()->first();

        self::assertEquals($productUnit, $quoteProductRequest->getProductUnit());
        self::assertEquals($productUnit->getCode(), $quoteProductRequest->getProductUnitCode());

        self::assertEquals($quantity, $quoteProductRequest->getQuantity());
        self::assertEquals($price, $quoteProductRequest->getPrice());

        self::assertEquals('|item|6', $quoteProductRequest->getChecksum());

        /* @var QuoteProductOffer $quoteProductOffer */
        $quoteProductOffer = $quoteProduct->getQuoteProductOffers()->first();

        self::assertEquals($productUnit, $quoteProductOffer->getProductUnit());
        self::assertEquals($productUnit->getCode(), $quoteProductOffer->getProductUnitCode());

        self::assertEquals($quantity, $quoteProductOffer->getQuantity());
        self::assertEquals($price, $quoteProductOffer->getPrice());

        self::assertEquals('|item|6', $quoteProductOffer->getChecksum());
    }

    /**
     * @dataProvider pricesDataProvider
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildFormWithProductKit(array $priceData, ?Price $expectedPrice): void
    {
        $requestId = 1;
        $requestProductItemId = 2;
        $productUnitCode = 'item';
        $productId = 123;
        $productSku = 'TEST SKU';
        $customerId = 3;
        $customerUserId = 4;
        $quantity = 6;
        $commentCustomer = 'comment 7';

        $request = $this->getEntity(Request::class, $requestId);
        $requestProductItem = $this->getEntity(RequestProductItem::class, $requestProductItemId);

        $productUnit = $this->getProductUnit($productUnitCode);
        $productUnit->setDefaultPrecision(0);
        $product = $this->getProduct($productSku, $productUnit);
        $product->setId($productId);
        $product->setType(Product::TYPE_KIT);

        $customer = $this->getEntity(Customer::class, $customerId);
        $customerUser = $this->getEntity(CustomerUser::class, $customerUserId);

        $this->assertPricesProviderCall($priceData, $product);

        $kitItemLineItem1Quantity = 2;
        $kitItemLineItem1SortOrder = 1;

        $product1 = (new ProductStub())
            ->setId(1)
            ->setSku('SKUPRODUCT1')
            ->setDefaultName('Product1 Name')
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($productUnit));

        $kitItem = (new ProductKitItemStub(1))
            ->setDefaultLabel('Base Unit')
            ->setMinimumQuantity(1)
            ->setMaximumQuantity(2)
            ->setOptional(false);

        $kitItemLineItemData = [
            ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY => $kitItem,
            'kitItemId' => $kitItem->getId(),
            'kitItemLabel' => $kitItem->getDefaultLabel(),
            'optional' => $kitItem->isOptional(),
            'minimumQuantity' => $kitItem->getMinimumQuantity(),
            'maximumQuantity' => $kitItem->getMaximumQuantity(),
            ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY => $product1,
            'productId' => $product1->getId(),
            'productName' => $product1->getName(),
            'productSku' => $product1->getSku(),
            ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_UNIT_KEY => $productUnit,
            'productUnitCode' => $productUnit->getCode(),
            'productUnitPrecision' => $productUnit->getDefaultPrecision(),
            ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_QUANTITY_KEY => $kitItemLineItem1Quantity,
            'sortOrder' => $kitItemLineItem1SortOrder,
        ];

        $requiredFieldNames = [
            'kitItemId',
            'kitItemLabel',
            'productId',
            'productName',
            'productSku',
            'productUnitCode',
        ];
        $kitItemLineItemsData = [
            $kitItemLineItemData,
        ];
        foreach ($requiredFieldNames as $fieldName) {
            $notValidKitItemLineItemData = $kitItemLineItemData;
            unset($notValidKitItemLineItemData[$fieldName]);
            $kitItemLineItemsData[] = $notValidKitItemLineItemData;
        }
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
                            'price' => $expectedPrice,
                            'quantity' => $quantity,
                            'productUnit' => $productUnit->getCode(),
                            'productUnitCode' => $productUnit->getCode(),
                            'requestProductItem' => $requestProductItem->getId(),
                        ],
                    ],
                    ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEMS_DATA_KEY => $kitItemLineItemsData,
                ],
            ],
        ];

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsFindProduct($productId, $product);

        $this->getExtension()->buildForm($this->getFormBuilder(), []);

        self::assertEquals($customer, $this->entity->getCustomer());
        self::assertEquals($customerUser, $this->entity->getCustomerUser());

        self::assertCount(1, $this->entity->getQuoteProducts());

        /* @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->entity->getQuoteProducts()->first();

        self::assertEquals($product, $quoteProduct->getProduct());
        self::assertEquals($product->getSku(), $quoteProduct->getProductSku());
        self::assertEquals($commentCustomer, $quoteProduct->getCommentCustomer());

        self::assertCount(1, $quoteProduct->getQuoteProductRequests());
        self::assertCount(1, $quoteProduct->getQuoteProductOffers());

        /* @var QuoteProductRequest $quoteProductRequest */
        $quoteProductRequest = $quoteProduct->getQuoteProductRequests()->first();

        self::assertEquals($productUnit, $quoteProductRequest->getProductUnit());
        self::assertEquals($productUnit->getCode(), $quoteProductRequest->getProductUnitCode());

        self::assertEquals($quantity, $quoteProductRequest->getQuantity());
        self::assertEquals($expectedPrice, $quoteProductRequest->getPrice());

        self::assertEquals('123|item|6', $quoteProductRequest->getChecksum());

        /* @var QuoteProductOffer $quoteProductOffer */
        $quoteProductOffer = $quoteProduct->getQuoteProductOffers()->first();

        self::assertEquals($productUnit, $quoteProductOffer->getProductUnit());
        self::assertEquals($productUnit->getCode(), $quoteProductOffer->getProductUnitCode());

        self::assertEquals($quantity, $quoteProductOffer->getQuantity());
        self::assertEquals($expectedPrice, $quoteProductOffer->getPrice());

        self::assertEquals('123|item|6', $quoteProductRequest->getChecksum());

        self::assertCount(1, $quoteProduct->getKitItemLineItems());
        /** @var QuoteProductKitItemLineItem $quoteProductKitItemLineItem */
        $quoteProductKitItemLineItem = $quoteProduct->getKitItemLineItems()->first();

        self::assertEquals($product1, $quoteProductKitItemLineItem->getProduct());
        self::assertEquals($product1->getSku(), $quoteProductKitItemLineItem->getProductSku());
        self::assertEquals($product1->getDenormalizedDefaultName(), $quoteProductKitItemLineItem->getProductName());
        self::assertEquals($kitItem, $quoteProductKitItemLineItem->getKitItem());
        self::assertEquals($kitItem->getDefaultLabel(), $quoteProductKitItemLineItem->getKitItemLabel());
        self::assertEquals($kitItem->isOptional(), $quoteProductKitItemLineItem->isOptional());
        self::assertEquals($kitItem->getMinimumQuantity(), $quoteProductKitItemLineItem->getMinimumQuantity());
        self::assertEquals($kitItem->getMaximumQuantity(), $quoteProductKitItemLineItem->getMaximumQuantity());
        self::assertEquals($productUnit, $quoteProductKitItemLineItem->getProductUnit());
        self::assertEquals($productUnit->getCode(), $quoteProductKitItemLineItem->getProductUnitCode());
        self::assertEquals(
            $productUnit->getDefaultPrecision(),
            $quoteProductKitItemLineItem->getProductUnitPrecision()
        );
        self::assertEquals($kitItemLineItem1Quantity, $quoteProductKitItemLineItem->getQuantity());
        self::assertEquals($kitItemLineItem1SortOrder, $quoteProductKitItemLineItem->getSortOrder());
    }

    public function pricesDataProvider(): array
    {
        return [
            'one price for kit product' => [
                'pricesData' => [
                    123 => [
                        '123|item|6' => [
                            [
                                'qty' => 2,
                                'unit' => 'item',
                                'price' => Price::create(7, 'USD'),
                            ],
                        ],
                    ],
                ],
                'expectedPrice' => Price::create(7, 'USD'),
            ],
            'two prices for kit product' => [
                'pricesData' => [
                    123 => [
                        '123|item|6' => [
                            [
                                'qty' => 1,
                                'unit' => 'item',
                                'price' => Price::create(7, 'USD'),
                            ],
                            [
                                'qty' => 5,
                                'unit' => 'item',
                                'price' => Price::create(3, 'USD'),
                            ],
                        ],
                    ],
                ],
                'expectedPrice' => Price::create(3, 'USD'),
            ],
            'all prices with not requested unit' => [
                'pricesData' => [
                    123 => [
                        '123|item|6' => [
                            [
                                'qty' => 1,
                                'unit' => 'set',
                                'price' => Price::create(7, 'USD'),
                            ],
                        ],
                    ],
                ],
                null,
            ],
            'no prices for requested configuration' => [
                'pricesData' => [
                    123 => [
                        '123|set|6' => [
                            [
                                'qty' => 1,
                                'unit' => 'item',
                                'price' => Price::create(7, 'USD'),
                            ],
                        ],
                    ],
                ],
                null,
            ],
            'no prices for requested product' => [
                'pricesData' => [],
                null,
            ],
        ];
    }

    private function assertPricesProviderCall(array $pricesData, Product $product): void
    {
        $tierPrices = [];
        foreach ($pricesData as $productId => $byChecksum) {
            foreach ($byChecksum as $checksum => $prices) {
                foreach ($prices as $priceRow) {
                    $tierPrices[$productId][$checksum][] = new ProductPriceDTO(
                        $product,
                        $priceRow['price'],
                        $priceRow['qty'],
                        (new ProductUnit())->setCode($priceRow['unit'])
                    );
                }
            }
        }

        $this->quoteProductPricesProvider
            ->expects(self::once())
            ->method('getProductLineItemsTierPrices')
            ->with($this->entity)
            ->willReturn($tierPrices);
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([QuoteType::class], QuoteDataStorageExtension::getExtendedTypes());
    }
}
