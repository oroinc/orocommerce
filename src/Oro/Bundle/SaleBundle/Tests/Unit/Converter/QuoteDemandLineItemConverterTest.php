<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Converter\ProductKitItemLineItemConverter;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutProductKitItemLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutValidationGroupsBySourceEntityProvider;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\SaleBundle\Converter\QuoteDemandLineItemConverter;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuoteDemandLineItemConverterTest extends TestCase
{
    private const VALIDATION_GROUPS = [['Default', 'quote_demand_line_item_to_checkout_line_item_convert']];

    private array $processedValidationGroups = [];

    private ValidatorInterface|MockObject $validator;

    private CheckoutValidationGroupsBySourceEntityProvider|MockObject $validationGroupsProvider;

    private QuoteDemandLineItemConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->validationGroupsProvider = $this->createMock(CheckoutValidationGroupsBySourceEntityProvider::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->processedValidationGroups = [new GroupSequence(self::VALIDATION_GROUPS)];

        $this->converter = new QuoteDemandLineItemConverter(
            new ProductKitItemLineItemConverter(),
            $this->validator,
            $this->validationGroupsProvider
        );
    }

    /**
     * @dataProvider isSourceSupportedDataProvider
     */
    public function testIsSourceSupported(bool $expected, mixed $source): void
    {
        self::assertEquals($expected, $this->converter->isSourceSupported($source));
    }

    public function isSourceSupportedDataProvider(): array
    {
        return [
            'positive' => ['expected' => true, 'source' => $this->createMock(QuoteDemand::class)],
            'unsupported instance' => ['expected' => false, 'source' => new \stdClass()],
        ];
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert(
        QuoteDemand $quoteDemand,
        array $checkoutLineItemsToValidate,
        array $violations,
        array $checkoutLineItems
    ): void {
        $this->validationGroupsProvider->expects(self::any())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, QuoteProductDemand::class)
            ->willReturn($this->processedValidationGroups);

        $this->validator->expects(self::any())
            ->method('validate')
            ->with(new ArrayCollection($checkoutLineItemsToValidate), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList($violations));

        /** @var CheckoutLineItem[] $items */
        $items = $this->converter->convert($quoteDemand);

        self::assertEquals($checkoutLineItems, array_values($items->toArray()));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function convertDataProvider(): array
    {
        $testComment = 'test comment';
        $checksum = 'quoteLineItemProductKit';

        $product1 = $this->getProduct(1, Product::STATUS_ENABLED, Product::INVENTORY_STATUS_IN_STOCK);
        $product4 = ($this->getProduct(4, Product::STATUS_ENABLED, Product::INVENTORY_STATUS_IN_STOCK))
            ->setType(Product::TYPE_KIT);

        $productUnit = (new ProductUnit())->setCode('item');

        $kitItem = new ProductKitItemStub(1);
        $quoteProductKitItemLineItem1 = $this->getQuoteProductKitItemLineItem($product1, $kitItem, 1, $productUnit);

        $quoteProduct = (new QuoteProduct())
            ->setComment($testComment)
            ->setProduct($product1);

        $quoteProductKit = (new QuoteProduct())
            ->setProduct($product4)
            ->addKitItemLineItem($quoteProductKitItemLineItem1);

        $price = Price::create(1, 'USD');

        $quoteProductOffer = (new QuoteProductOffer())
            ->setQuoteProduct($quoteProduct)
            ->setProductUnit($productUnit)
            ->setPrice($price);

        $quoteProductKitOffer = (new QuoteProductOffer())
            ->setQuoteProduct($quoteProductKit)
            ->setProductUnit($productUnit)
            ->setPrice($price);
        $quoteProductKitOffer->loadKitItemLineItems();

        $quoteDemand = new QuoteDemand();

        $quoteProductDemand = new QuoteProductDemand($quoteDemand, $quoteProductOffer, 10);

        $quoteProductKitDemand = (new QuoteProductDemand($quoteDemand, new QuoteProductOffer(), 11))
            ->setChecksum($checksum);
        $quoteProductKitDemand->setQuoteProductOffer($quoteProductKitOffer);

        $quoteDemand->addDemandProduct($quoteProductDemand);
        $quoteDemand->addDemandProduct($quoteProductKitDemand);

        $quoteDemandWithDeletedProductKit = new QuoteDemand();

        $quoteDeletedProduct = clone $quoteProductKit;
        $quoteDeletedProduct->setProduct(null);

        $quoteDeletedProductKitOffer = clone $quoteProductKitOffer;
        $quoteDeletedProductKitOffer->setQuoteProduct($quoteDeletedProduct);

        $quoteDeletedProductKitDemand = clone $quoteProductKitDemand;
        $quoteDeletedProductKitDemand->setQuoteProductOffer($quoteDeletedProductKitOffer);

        $quoteDemandWithDeletedProductKit->addDemandProduct($quoteDeletedProductKitDemand);

        $checkoutLineItem10Items = (new CheckoutLineItem())
            ->setFromExternalSource(true)
            ->setPriceFixed(true)
            ->setProduct($product1)
            ->setProductSku($product1->getSku())
            ->setProductUnit($productUnit)
            ->setProductUnitCode($productUnit->getCode())
            ->setQuantity(10)
            ->setPrice($price)
            ->setComment($testComment);

        $checkoutKitItemLineItem1 = $this->getCheckoutProductKitItemLineItem(
            $product1,
            $kitItem,
            $productUnit,
            1
        );
        $checkoutLineItemProductKit = (new CheckoutLineItem())
            ->setFromExternalSource(true)
            ->setPriceFixed(true)
            ->setProduct($product4)
            ->setProductSku($product4->getSku())
            ->setProductUnit($productUnit)
            ->setProductUnitCode($productUnit->getCode())
            ->setQuantity(11)
            ->setPrice($price)
            ->setChecksum($checksum)
            ->addKitItemLineItem($checkoutKitItemLineItem1);

        $checkoutKitItemLineItem1DeletedProductKit = $this->getCheckoutProductKitItemLineItem(
            $product1,
            $kitItem,
            $productUnit,
            1
        );
        $checkoutLineItemDeletedProductKit = (new CheckoutLineItem())
            ->setFromExternalSource(true)
            ->setPriceFixed(true)
            ->setProduct(null)
            ->setProductSku($product4->getSku())
            ->setProductUnit($productUnit)
            ->setProductUnitCode($productUnit->getCode())
            ->setQuantity(11)
            ->setPrice($price)
            ->setChecksum($checksum)
            ->addKitItemLineItem($checkoutKitItemLineItem1DeletedProductKit);

        $violation1 = new ConstraintViolation(
            'Invalid value',
            '',
            [],
            '',
            new PropertyPath('[0].product'),
            ''
        );
        $violation2 = new ConstraintViolation(
            'Invalid value',
            '',
            [],
            '',
            new PropertyPath('[1].kitItemLineItems[0].product'),
            ''
        );

        return [
            'no line items' => [
                'quoteDemand' => new QuoteDemand(),
                'checkoutLineItemsToValidate' => [],
                'violations' => [],
                'checkoutLineItems' => [],
            ],
            'not valid line item' => [
                'quoteDemand' => $quoteDemand,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                    $checkoutLineItemProductKit,
                ],
                'violations' => [$violation1],
                'checkoutLineItems' => [
                    $checkoutLineItemProductKit,
                ],
            ],
            'valid line item' => [
                'quoteDemand' => $quoteDemand,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                    $checkoutLineItemProductKit,
                ],
                'violations' => [],
                'checkoutLineItems' => [
                    $checkoutLineItem10Items,
                    $checkoutLineItemProductKit,
                ],
            ],
            'not valid kit item line item' => [
                'quoteDemand' => $quoteDemand,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                    $checkoutLineItemProductKit,
                ],
                'violations' => [$violation2],
                'checkoutLineItems' => [
                    $checkoutLineItem10Items,
                ],
            ],
            'not valid kit item line item with deleted product' => [
                'quoteDemand' => $quoteDemandWithDeletedProductKit,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItemDeletedProductKit,
                ],
                'violations' => [
                    $violation1,
                ],
                'checkoutLineItems' => [],
            ],
            'valid kit item line item' => [
                'quoteDemand' => $quoteDemand,
                'checkoutLineItemsToValidate' => [
                    $checkoutLineItem10Items,
                    $checkoutLineItemProductKit,
                ],
                'violations' => [],
                'checkoutLineItems' => [
                    $checkoutLineItem10Items,
                    $checkoutLineItemProductKit,
                ],
            ],
        ];
    }

    public function testConvertWithFreeFormProduct(): void
    {
        $productSku = 'SKU';
        $freeFormProduct = 'TEST';

        $quoteProduct = (new QuoteProduct())
            ->setFreeFormProduct($freeFormProduct)
            ->setProductSku($productSku);

        $quoteProductOffer = (new QuoteProductOffer())
            ->setQuoteProduct($quoteProduct);

        $quoteDemand = new QuoteDemand();

        $quoteProductDemand = new QuoteProductDemand($quoteDemand, $quoteProductOffer, 10);

        $quoteDemand->addDemandProduct($quoteProductDemand);

        $this->validationGroupsProvider->expects(self::any())
            ->method('getValidationGroupsBySourceEntity')
            ->with(self::VALIDATION_GROUPS, QuoteProductDemand::class)
            ->willReturn($this->processedValidationGroups);

        $checkoutLineItemToValidate = (new CheckoutLineItem())
            ->setFromExternalSource(true)
            ->setPriceFixed(true)
            ->setProductSku($productSku)
            ->setFreeFormProduct($freeFormProduct)
            ->setQuantity(10);

        $checkoutLineItemsToValidate = [
            $checkoutLineItemToValidate,
        ];

        $this->validator->expects(self::once())
            ->method('validate')
            ->with(new ArrayCollection($checkoutLineItemsToValidate), null, $this->processedValidationGroups)
            ->willReturn(new ConstraintViolationList([]));

        /** @var CheckoutLineItem[] $items */
        $items = $this->converter->convert($quoteDemand);
        self::assertInstanceOf(ArrayCollection::class, $items);
        self::assertCount(1, $items);

        self::assertInstanceOf(CheckoutLineItem::class, $items[0]);
        self::assertSame($productSku, $items[0]->getProductSku());
        self::assertSame($freeFormProduct, $items[0]->getFreeFormProduct());
    }

    private function getQuoteProductKitItemLineItem(
        ?Product $product,
        ?ProductKitItem $kitItem,
        float $quantity,
        ?ProductUnit $productUnit
    ): QuoteProductKitItemLineItem {
        return (new QuoteProductKitItemLineItem())
            ->setProduct($product)
            ->setKitItem($kitItem)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setSortOrder(1);
    }

    private function getCheckoutProductKitItemLineItem(
        ?Product $product,
        ?ProductKitItem $kitItem,
        ?ProductUnit $productUnit,
        float $quantity
    ): CheckoutProductKitItemLineItem {
        return (new CheckoutProductKitItemLineItem())
            ->setProduct($product)
            ->setKitItem($kitItem)
            ->setProductUnit($productUnit)
            ->setQuantity($quantity)
            ->setSortOrder(1)
            ->setPriceFixed(false);
    }

    private function getProduct(int $id, string $status, string $inventoryStatus): Product
    {
        return (new Product())
            ->setId($id)
            ->setStatus($status)
            ->setInventoryStatus(new TestEnumValue('test', 'Test', $inventoryStatus));
    }
}
