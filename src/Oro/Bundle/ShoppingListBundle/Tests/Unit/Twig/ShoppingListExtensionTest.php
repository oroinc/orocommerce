<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Layout\DataProvider\LayoutButtonProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Oro\Bundle\ShoppingListBundle\Twig\ShoppingListExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShoppingListExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ShoppingListLimitManager|MockObject $shoppingListLimitManager;

    private ShoppingListUrlProvider|MockObject $shoppingListUrlProvider;

    private LayoutButtonProvider|MockObject $layoutButtonProvider;

    private FeatureChecker|MockObject $featureChecker;

    private ShoppingListExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);
        $this->shoppingListUrlProvider = $this->createMock(ShoppingListUrlProvider::class);
        $this->layoutButtonProvider = $this->createMock(LayoutButtonProvider::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $container = self::getContainerBuilder()
            ->add(ShoppingListLimitManager::class, $this->shoppingListLimitManager)
            ->add(ShoppingListUrlProvider::class, $this->shoppingListUrlProvider)
            ->add(LayoutButtonProvider::class, $this->layoutButtonProvider)
            ->add(FeatureChecker::class, $this->featureChecker)
            ->getContainer($this);

        $this->extension = new ShoppingListExtension($container);
    }

    public function testIsConfigurableSimple(): void
    {
        $this->shoppingListLimitManager->expects($this->once())
            ->method('isOnlyOneEnabled')
            ->willReturn(false);

        $this->assertFalse(
            self::callTwigFunction($this->extension, 'is_one_shopping_list_enabled', [])
        );
    }

    public function testShoppingListFrontendUrl(): void
    {
        $this->shoppingListUrlProvider->expects($this->once())
            ->method('getFrontendUrl')
            ->willReturn('/test/url');

        $this->assertEquals(
            '/test/url',
            $this->callTwigFunction($this->extension, 'oro_shopping_list_frontend_url', [])
        );
    }

    public function testGetShoppingListWidgetButtons(): void
    {
        $button1 = $this->createMock(ButtonInterface::class);
        $button1->expects($this->once())
            ->method('getName')
            ->willReturn('b2b_flow_checkout_start_from_shoppinglist');
        $button2 = $this->createMock(ButtonInterface::class);
        $button2->expects($this->once())
            ->method('getName')
            ->willReturn('any wrong name');

        $shoppingList = new ShoppingList();

        $this->layoutButtonProvider->expects($this->once())
            ->method('getAll')
            ->with($shoppingList)
            ->willReturn([$button1, $button2]);

        self::assertEquals(
            [$button1],
            self::callTwigFunction($this->extension, 'get_shopping_list_widget_buttons', [$shoppingList])
        );
    }

    /**
     * @dataProvider lineItemDataProvider
     */
    public function testGetVisibleProduct(
        bool $isVariant,
        bool $hasParentProduct,
        bool $isRestrictionEnabled,
        bool $expectedParentProduct,
        bool $expectedViewable
    ): void {
        [$lineItem, $product, $configProduct] = $this->createLineItem($isVariant, $hasParentProduct);

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('simple_variations_view_restriction')
            ->willReturn($isRestrictionEnabled);

        self::assertSame(
            $expectedParentProduct ? $configProduct : $product,
            self::callTwigFunction($this->extension, 'get_visible_product', [$lineItem])
        );
    }

    /**
     * @dataProvider lineItemDataProvider
     */
    public function testIsLineItemProductViewable(
        bool $isVariant,
        bool $hasParentProduct,
        bool $isRestrictionEnabled,
        bool $expectedParentProduct,
        bool $expectedViewable
    ): void {
        [$lineItem] = $this->createLineItem($isVariant, $hasParentProduct);

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('simple_variations_view_restriction')
            ->willReturn($isRestrictionEnabled);

        self::assertSame(
            $expectedViewable,
            self::callTwigFunction($this->extension, 'is_line_item_product_viewable', [$lineItem])
        );
    }

    public function lineItemDataProvider(): array
    {
        return [
            'not variant, restriction enabled' => [
                'isVariant' => false,
                'hasParentProduct' => false,
                'isRestrictionEnabled' => true,
                'expectedParentProduct' => false,
                'expectedViewable' => true,
            ],
            'variant with parent product, restriction enabled' => [
                'isVariant' => true,
                'hasParentProduct' => true,
                'isRestrictionEnabled' => true,
                'expectedParentProduct' => true,
                'expectedViewable' => true,
            ],
            'variant without parent product, restriction enabled' => [
                'isVariant' => true,
                'hasParentProduct' => false,
                'isRestrictionEnabled' => true,
                'expectedParentProduct' => false,
                'expectedViewable' => false,
            ],
            'variant with parent product, restriction disabled' => [
                'isVariant' => true,
                'hasParentProduct' => true,
                'isRestrictionEnabled' => false,
                'expectedParentProduct' => false,
                'expectedViewable' => true,
            ],
            'variant without parent product, restriction disabled' => [
                'isVariant' => true,
                'hasParentProduct' => false,
                'isRestrictionEnabled' => false,
                'expectedParentProduct' => false,
                'expectedViewable' => true,
            ],
        ];
    }

    public function testVariantCheckIsPerformedOncePerProduct(): void
    {
        $variantLinks = $this->createMock(Collection::class);
        $variantLinks->expects(self::once())
            ->method('count')
            ->willReturn(1);
        $product = $this->createMock(Product::class);
        $product->expects(self::any())
            ->method('getParentVariantLinks')
            ->willReturn($variantLinks);
        $lineItem = new LineItem();
        $lineItem->setProduct($product);

        $this->featureChecker->expects(self::exactly(2))
            ->method('isFeatureEnabled')
            ->with('simple_variations_view_restriction')
            ->willReturn(true);

        self::assertSame($product, $this->extension->getVisibleProduct($lineItem));
        self::assertFalse($this->extension->isLineItemProductViewable($lineItem));
    }

    private function createLineItem(bool $isVariant, bool $hasParentProduct): array
    {
        $product = (new Product())->setId(1);
        $configProduct = (new Product())->setId(2);
        if ($isVariant) {
            $product->addParentVariantLink(new ProductVariantLink($configProduct, $product));
        }

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        if ($hasParentProduct) {
            $lineItem->setParentProduct($configProduct);
        }

        return [$lineItem, $product, $configProduct];
    }
}
