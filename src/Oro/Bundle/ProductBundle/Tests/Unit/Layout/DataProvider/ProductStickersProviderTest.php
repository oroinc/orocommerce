<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductStickersProvider;
use Oro\Bundle\ProductBundle\Model\ProductView;

class ProductStickersProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ProductStickersProvider */
    private $productStickersProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->productStickersProvider = new ProductStickersProvider($this->configManager);
    }

    /**
     * @dataProvider getStickersByProductDataProvider
     */
    public function testGetStickersByProduct(Product $product, array $stickers)
    {
        self::assertSame($stickers, $this->productStickersProvider->getStickers($product));
    }

    public function getStickersByProductDataProvider(): array
    {
        $product = new Product();
        $product->setNewArrival(false);

        $newArrivalProduct = new Product();
        $newArrivalProduct->setNewArrival(true);

        return [
            [$product, []],
            [$newArrivalProduct, [['type' => 'new_arrival']]]
        ];
    }

    /**
     * @dataProvider getStickersByProductViewDataProvider
     */
    public function testGetStickersByProductView(ProductView $product, array $stickers)
    {
        self::assertSame($stickers, $this->productStickersProvider->getStickers($product));
    }

    public function getStickersByProductViewDataProvider(): array
    {
        $product = new ProductView();
        $product->set('newArrival', false);

        $newArrivalProduct = new ProductView();
        $newArrivalProduct->set('newArrival', true);

        return [
            [$product, []],
            [$newArrivalProduct, [['type' => 'new_arrival']]]
        ];
    }

    /**
     * @dataProvider getStickersForProductsDataProvider
     */
    public function testGetStickersForProducts($products, array $stickers)
    {
        self::assertSame($stickers, $this->productStickersProvider->getStickersForProducts($products));
    }

    public function getStickersForProductsDataProvider(): array
    {
        $product = new ProductView();
        $product->set('id', 2);
        $product->set('newArrival', false);

        $newArrivalProduct = new ProductView();
        $newArrivalProduct->set('id', 2);
        $newArrivalProduct->set('newArrival', true);

        return [
            [[$product], [$product->getId() => []]],
            [[$newArrivalProduct], [$newArrivalProduct->getId() => [['type' => 'new_arrival']]]]
        ];
    }

    /**
     * @dataProvider isStickersEnabledOnViewDataProvider
     */
    public function testIsStickersEnabledOnView(bool $value)
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_product.product_promotion_show_on_product_view')
            ->willReturn($value);

        self::assertSame($value, $this->productStickersProvider->isStickersEnabledOnView());
    }

    public function isStickersEnabledOnViewDataProvider(): array
    {
        return [
            [false],
            [true]
        ];
    }
}
