<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductsProvider;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FrontendShoppingListProductsProviderTest extends TestCase
{
    use EntityTrait;

    private LineItemRepository|MockObject $lineItemRepository;

    private FrontendProductPricesDataProvider|MockObject $frontendProductPricesDataProvider;

    private ShoppingListLineItemsDataProvider|MockObject $shoppingListLineItemsDataProvider;

    private ProductPriceFormatter|MockObject $productPriceFormatter;

    private ProductLineItemPriceProviderInterface|MockObject $productLineItemPriceProvider;

    private FrontendShoppingListProductsProvider $provider;

    protected function setUp(): void
    {
        $this->lineItemRepository = $this->createMock(LineItemRepository::class);
        $this->frontendProductPricesDataProvider = $this->createMock(FrontendProductPricesDataProvider::class);
        $this->shoppingListLineItemsDataProvider = $this->createMock(ShoppingListLineItemsDataProvider::class);
        $this->productPriceFormatter = $this->createMock(ProductPriceFormatter::class);
        $this->productLineItemPriceProvider = $this->createMock(ProductLineItemPriceProviderInterface::class);

        $this->provider = new FrontendShoppingListProductsProvider(
            $this->lineItemRepository,
            $this->frontendProductPricesDataProvider,
            $this->shoppingListLineItemsDataProvider,
            $this->productPriceFormatter,
            $this->productLineItemPriceProvider
        );
    }

    public function testGetAllPrices(): void
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, ['id' => 2]);

        /** @var LineItem[] $lineItems */
        $lineItems = [
            $this->getEntity(LineItem::class, ['id' => 1]),
        ];
        $prices = ['price_1', 'price_2'];
        $expected = ['price_1', 'price_2'];

        $this->shoppingListLineItemsDataProvider->expects(self::once())
            ->method('getShoppingListLineItems')
            ->with($shoppingList)
            ->willReturn($lineItems);

        $this->frontendProductPricesDataProvider->expects(self::once())
            ->method('getAllPricesForLineItems')
            ->with($lineItems)
            ->willReturn($prices);

        $this->productPriceFormatter->expects(self::once())
            ->method('formatProducts')
            ->with($prices)
            ->willReturn($expected);

        $result = $this->provider->getAllPrices($shoppingList);
        self::assertEquals($expected, $result);
    }

    public function testGetAllPricesWithoutShoppingList(): void
    {
        $this->shoppingListLineItemsDataProvider->expects(self::never())
            ->method('getShoppingListLineItems');
        $this->frontendProductPricesDataProvider->expects(self::never())
            ->method('getAllPricesForLineItems');
        $this->productPriceFormatter->expects(self::never())
            ->method('formatProducts');

        $this->provider->getAllPrices();
    }

    public function testGetMatchedPrice(): void
    {
        $shoppingList1 = (new ShoppingListStub())->setId(11);
        $unitItem = (new ProductUnit())->setCode('item');
        $product = (new ProductStub())->setId(1100)->setType(Product::TYPE_SIMPLE);
        $lineItem1 = (new LineItem())
            ->setProduct($product)
            ->setUnit($unitItem);
        $unitEach = (new ProductUnit())->setCode('each');
        $productKit = (new ProductStub())->setId(1101)->setType(Product::TYPE_KIT);
        $lineItem2 = (new LineItem())
            ->setProduct($productKit)
            ->setUnit($unitEach)
            ->setChecksum('sample_checksum');

        $productLineItemPrice1 = new ProductLineItemPrice($lineItem1, Price::create(12.3456, 'USD'), 12.3456 * 2);
        $productLineItemPrice2 = new ProductLineItemPrice($lineItem2, Price::create(34.5678, 'USD'), 34.5678 * 2);

        $this->shoppingListLineItemsDataProvider
            ->expects(self::once())
            ->method('getShoppingListLineItems')
            ->with($shoppingList1)
            ->willReturn([$lineItem1, $lineItem2]);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem1, $lineItem2])
            ->willReturn([$productLineItemPrice1, $productLineItemPrice2]);

        self::assertEquals(
            [
                $product->getId() => [$lineItem1->getProductUnitCode() => $productLineItemPrice1->getPrice()],
                $productKit->getId() => [
                    $lineItem2->getProductUnitCode() => [
                        $lineItem2->getChecksum() => $productLineItemPrice2->getPrice(),
                    ],
                ],
            ],
            $this->provider->getMatchedPrice($shoppingList1)
        );
    }

    public function testGetMatchedPrices(): void
    {
        $shoppingList1 = (new ShoppingListStub())->setId(11);
        $shoppingList2 = (new ShoppingListStub())->setId(22);
        $unitItem = (new ProductUnit())->setCode('item');
        $product = (new ProductStub())->setId(1100)->setType(Product::TYPE_SIMPLE);
        $lineItem1 = (new LineItem())
            ->setProduct($product)
            ->setUnit($unitItem);
        $unitEach = (new ProductUnit())->setCode('each');
        $productKit = (new ProductStub())->setId(1101)->setType(Product::TYPE_KIT);
        $lineItem2 = (new LineItem())
            ->setProduct($productKit)
            ->setUnit($unitEach)
            ->setChecksum('sample_checksum');

        $productLineItemPrice1 = new ProductLineItemPrice($lineItem1, Price::create(12.3456, 'USD'), 12.3456 * 2);
        $productLineItemPrice2 = new ProductLineItemPrice($lineItem2, Price::create(34.5678, 'USD'), 34.5678 * 2);

        $this->shoppingListLineItemsDataProvider
            ->expects(self::exactly(2))
            ->method('getShoppingListLineItems')
            ->willReturnMap([
                [$shoppingList1, [$lineItem1, $lineItem2]],
                [$shoppingList2, []],
            ]);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem1, $lineItem2])
            ->willReturn([$productLineItemPrice1, $productLineItemPrice2]);

        self::assertEquals(
            [
                $shoppingList1->getId() => [
                    $product->getId() => [$lineItem1->getProductUnitCode() => $productLineItemPrice1->getPrice()],
                    $productKit->getId() => [
                        $lineItem2->getProductUnitCode() => [
                            $lineItem2->getChecksum() => $productLineItemPrice2->getPrice(),
                        ],
                    ],
                ],
                $shoppingList2->getId() => [],
            ],
            $this->provider->getMatchedPrices([$shoppingList1, $shoppingList2])
        );
    }

    public function testGetProductLineItemPricesForShoppingListsWhenNoShoppingLists(): void
    {
        self::assertEquals([], $this->provider->getProductLineItemPricesForShoppingLists([]));
    }

    public function testGetProductLineItemPricesForShoppingLists(): void
    {
        $shoppingList1 = (new ShoppingListStub())->setId(11);
        $lineItem1 = new LineItem();
        $lineItem2 = new LineItem();
        $shoppingList2 = (new ShoppingListStub())->setId(22);
        $productLineItemPrice1 = $this->createMock(ProductLineItemPrice::class);
        $productLineItemPrice2 = $this->createMock(ProductLineItemPrice::class);

        $this->shoppingListLineItemsDataProvider
            ->expects(self::exactly(2))
            ->method('getShoppingListLineItems')
            ->willReturnMap([
                [$shoppingList1, [$lineItem1, $lineItem2]],
                [$shoppingList2, []],
            ]);

        $this->productLineItemPriceProvider
            ->expects(self::once())
            ->method('getProductLineItemsPrices')
            ->with([$lineItem1, $lineItem2])
            ->willReturn([$productLineItemPrice1, $productLineItemPrice2]);

        self::assertEquals(
            [
                $shoppingList1->getId() => [$productLineItemPrice1, $productLineItemPrice2],
                $shoppingList2->getId() => [],
            ],
            $this->provider->getProductLineItemPricesForShoppingLists([$shoppingList1, $shoppingList2])
        );
    }

    public function testGetLastProductsGroupedByShoppingList(): void
    {
        $shoppingLists = [$this->getEntity(ShoppingList::class)];
        $productCount = 1;
        $localization = $this->getEntity(Localization::class);
        $this->lineItemRepository->expects(self::once())
            ->method('getLastProductsGroupedByShoppingList')
            ->with($shoppingLists, $productCount, $localization);

        $this->provider->getLastProductsGroupedByShoppingList($shoppingLists, $productCount, $localization);
    }

    public function testThatProductsReturnedFromShoppingList()
    {
        $this->shoppingListLineItemsDataProvider
            ->expects(self::once())
            ->method('getShoppingListLineItems')
            ->with($shoppingList = $this->createMock(ShoppingList::class))
            ->willReturn([$lineItem = $this->createMock(LineItem::class)]);

        $lineItem
            ->expects(self::once())
            ->method('getProduct')
            ->willReturn($product = $this->createMock(Product::class));

        self::assertEquals(
            [$product],
            $this->provider->getShoppingListProducts($shoppingList)
        );
    }
}
