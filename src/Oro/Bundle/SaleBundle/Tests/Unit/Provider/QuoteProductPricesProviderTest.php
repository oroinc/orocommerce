<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProviderInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductKitItemLineItem;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Provider\QuoteProductPricesProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteProductPricesProviderTest extends TestCase
{
    private ProductPriceProviderInterface|MockObject $productPriceProvider;

    private ProductPriceScopeCriteriaFactoryInterface|MockObject $priceScopeCriteriaFactory;

    private ProductLineItemProductPriceProviderInterface|MockObject $lineItemProductPriceProvider;

    private QuoteProductPricesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->productPriceProvider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = $this->createMock(ProductPriceScopeCriteriaFactoryInterface::class);
        $this->lineItemProductPriceProvider = $this->createMock(ProductLineItemProductPriceProviderInterface::class);
        $userCurrencyManager = $this->createMock(UserCurrencyManager::class);

        $this->provider = new QuoteProductPricesProvider(
            $this->productPriceProvider,
            $this->priceScopeCriteriaFactory,
            $this->lineItemProductPriceProvider,
            $userCurrencyManager
        );

        $this->productPriceProvider
            ->method('getSupportedCurrencies')
            ->willReturn(['USD']);

        $userCurrencyManager
            ->method('getAvailableCurrencies')
            ->willReturn(['USD']);
    }

    public function testGetTierPricesWhenNoLineItems(): void
    {
        $quote = new Quote();

        $this->lineItemProductPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getProductLineItemsTierPrices($quote));
    }

    public function testGetTierPricesWhenNoLineItemsWithProduct(): void
    {
        $quoteProduct = (new QuoteProduct())
            ->setProduct(new ProductStub());
        $quote = (new Quote())
            ->addQuoteProduct($quoteProduct);

        $this->lineItemProductPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getProductLineItemsTierPrices($quote));
    }

    public function testGetTierPricesWhenHasLineItemWithProductButNoPrices(): void
    {
        $product = (new ProductStub())->setId(42);
        $website = new Website();
        $quoteProductOffer = (new QuoteProductOffer())
            ->setChecksum('sample-checksum');
        $quoteProduct = (new QuoteProduct())
            ->setProduct($product)
            ->addQuoteProductOffer($quoteProductOffer);
        $quote = (new Quote())
            ->addQuoteProduct($quoteProduct)
            ->setWebsite($website);

        $currency = 'USD';

        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->willReturn([]);

        $this->lineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with($quoteProductOffer, new ProductPriceCollectionDTO(), $currency)
            ->willReturn([]);

        self::assertSame(
            [$product->getId() => [$quoteProductOffer->getChecksum() => []]],
            $this->provider->getProductLineItemsTierPrices($quote)
        );
    }

    public function testGetTierPricesWhenHasLineItemWithProductAndHasPrices(): void
    {
        $product = (new ProductStub())->setId(42);
        $website = new Website();
        $quoteProductOffer = (new QuoteProductOffer())
            ->setChecksum('sample-checksum');
        $quoteProduct = (new QuoteProduct())
            ->setProduct($product)
            ->addQuoteProductOffer($quoteProductOffer);
        $quote = (new Quote())
            ->addQuoteProduct($quoteProduct)
            ->setWebsite($website);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->willReturn([$product->getId() => [$productPrice1, $productPrice2]]);

        $this->lineItemProductPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemProductPrices')
            ->with(
                $quoteProductOffer,
                new ProductPriceCollectionDTO([$productPrice1, $productPrice2]),
                'USD'
            )
            ->willReturn([$productPrice1, $productPrice2]);

        self::assertSame(
            [
                $product->getId() => [
                    $quoteProductOffer->getChecksum() => [$productPrice1, $productPrice2],
                ],
            ],
            $this->provider->getProductLineItemsTierPrices($quote)
        );
    }

    public function testGetTierPricesWhenHasLineItemWithProductKit(): void
    {
        $product = (new ProductStub())
            ->setId(42)
            ->setType(Product::TYPE_KIT);
        $website = new Website();
        $quoteProduct1Offer = (new QuoteProductOffer())
            ->setChecksum('sample-checksum-1');
        $quoteProduct1 = (new QuoteProduct())
            ->setProduct($product)
            ->addQuoteProductOffer($quoteProduct1Offer);
        $quoteProduct2Offer = (new QuoteProductOffer())
            ->setChecksum('sample-checksum-2');
        $quoteProduct2 = (new QuoteProduct())
            ->setProduct($product)
            ->addQuoteProductOffer($quoteProduct2Offer);
        $quote = (new Quote())
            ->addQuoteProduct($quoteProduct1)
            ->addQuoteProduct($quoteProduct2)
            ->setWebsite($website);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrice3 = $this->createMock(ProductPriceInterface::class);
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->willReturn([$product->getId() => [$productPrice1, $productPrice2, $productPrice3]]);

        $productPriceCollection = new ProductPriceCollectionDTO([$productPrice1, $productPrice2, $productPrice3]);
        $this->lineItemProductPriceProvider
            ->expects(self::exactly(2))
            ->method('getProductLineItemProductPrices')
            ->withConsecutive(
                [$quoteProduct1Offer, $productPriceCollection, 'USD'],
                [$quoteProduct2Offer, $productPriceCollection, 'USD'],
            )
            ->willReturnOnConsecutiveCalls(
                [$productPrice1, $productPrice2],
                [$productPrice3],
            );

        self::assertSame(
            [
                $product->getId() => [
                    $quoteProduct1Offer->getChecksum() => [$productPrice1, $productPrice2],
                    $quoteProduct2Offer->getChecksum() => [$productPrice3],
                ],
            ],
            $this->provider->getProductLineItemsTierPrices($quote)
        );
    }

    public function testGetProductPricesWhenNoQuoteProducts(): void
    {
        $this->productPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method(self::anything());

        self::assertSame([], $this->provider->getProductPrices(new Quote()));
    }

    public function testGetProductPricesWhenNoQuoteProductsWithProducts(): void
    {
        $this->productPriceProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->priceScopeCriteriaFactory
            ->expects(self::never())
            ->method(self::anything());

        $quoteProduct = (new QuoteProduct())
            ->addQuoteProductOffer(new QuoteProductOffer());

        self::assertSame([], $this->provider->getProductPrices((new Quote())->addQuoteProduct($quoteProduct)));
    }

    public function testGetProductPricesWhenHasQuoteProductWithProduct(): void
    {
        $product = (new ProductStub())->setId(10);
        $quoteProduct = (new QuoteProduct())
            ->setProduct($product);
        $quote = (new Quote())
            ->addQuoteProduct($quoteProduct);
        $currency = 'USD';
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getSupportedCurrencies')
            ->willReturn([$currency]);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($quote)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$product->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($priceScopeCriteria, [$product], [$currency])
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($quote));
    }

    public function testGetProductPricesWhenHasQuoteProductWithProductKitAndNoKitItemLineItems(): void
    {
        $kitItem1Product1 = (new ProductStub())->setId(100);
        $kitItem1Product2 = (new ProductStub())->setId(101);
        $kitItem1 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product2));
        $kitItem2Product1 = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product1));
        $productKit = (new ProductStub())
            ->setId(10)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productKit);
        $quote = (new Quote())
            ->addQuoteProduct($quoteProduct);

        $currency = 'USD';
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getSupportedCurrencies')
            ->willReturn([$currency]);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($quote)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$productKit->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $priceScopeCriteria,
                [$productKit, $kitItem1Product1, $kitItem1Product2, $kitItem2Product1],
                [$currency]
            )
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($quote));
    }

    public function testGetProductPricesWhenHasQuoteProductWithProductKitAndKitItemLineItem(): void
    {
        $kitItem1Product1 = (new ProductStub())->setId(100);
        $kitItem1Product2 = (new ProductStub())->setId(101);
        $kitItem1 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product2));
        $kitItem2Product1 = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product1));
        $productKit = (new ProductStub())
            ->setId(10)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $kitItemLineItem1Product = new Product();
        $kitItemLineItem1 = (new QuoteProductKitItemLineItem())
            ->setProduct($kitItemLineItem1Product);
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem1);
        $quote = (new Quote())
            ->addQuoteProduct($quoteProduct);

        $currency = 'USD';
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getSupportedCurrencies')
            ->willReturn([$currency]);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($quote)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$productKit->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $priceScopeCriteria,
                [$productKit, $kitItemLineItem1Product, $kitItem1Product1, $kitItem1Product2, $kitItem2Product1],
                [$currency]
            )
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($quote));
    }

    public function testGetProductPricesWhenHasQuoteProductWithProductKitAndNoKitItemLineItemProduct(): void
    {
        $kitItem1Product1 = (new ProductStub())->setId(100);
        $kitItem1Product2 = (new ProductStub())->setId(101);
        $kitItem1 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem1Product2));
        $kitItem2Product1 = (new ProductStub())->setId(200);
        $kitItem2 = (new ProductKitItemStub())
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($kitItem2Product1));
        $productKit = (new ProductStub())
            ->setId(10)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($kitItem1)
            ->addKitItem($kitItem2);
        $kitItemLineItem1 = new QuoteProductKitItemLineItem();
        $quoteProduct = (new QuoteProduct())
            ->setProduct($productKit)
            ->addKitItemLineItem($kitItemLineItem1);
        $quote = (new Quote())
            ->addQuoteProduct($quoteProduct);

        $currency = 'USD';
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getSupportedCurrencies')
            ->willReturn([$currency]);

        $priceScopeCriteria = $this->createMock(ProductPriceScopeCriteriaInterface::class);
        $this->priceScopeCriteriaFactory
            ->expects(self::once())
            ->method('createByContext')
            ->with($quote)
            ->willReturn($priceScopeCriteria);

        $productPrice1 = $this->createMock(ProductPriceInterface::class);
        $productPrice2 = $this->createMock(ProductPriceInterface::class);
        $productPrices = [$productKit->getId() => [$productPrice1, $productPrice2]];
        $this->productPriceProvider
            ->expects(self::once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with(
                $priceScopeCriteria,
                [$productKit, $kitItem1Product1, $kitItem1Product2, $kitItem2Product1],
                [$currency]
            )
            ->willReturn($productPrices);

        self::assertSame($productPrices, $this->provider->getProductPrices($quote));
    }

    public function testHasEmptyPriceTrue(): void
    {
        $quote = new Quote();

        $quoteProduct = (new QuoteProduct())
            ->setProduct(new ProductStub())
            ->addQuoteProductOffer(new QuoteProductOffer());
        $quote->addQuoteProduct($quoteProduct);

        self::assertTrue($this->provider->hasEmptyPrice($quote));
    }

    public function testHasEmptyPriceFalse(): void
    {
        $quote = new Quote();
        $quoteProduct = new QuoteProduct();

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        $price = new Price();
        $price->setCurrency('USD');
        $price->setValue(12.345);

        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setPrice($price);
        $quoteProduct->addQuoteProductOffer($quoteProductOffer);

        $quote->addQuoteProduct($quoteProduct);

        self::assertFalse($this->provider->hasEmptyPrice($quote));
    }
}
