<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Layout\DataProvider\LayoutButtonProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
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

    public function testGetVisibleProduct(): void
    {
        $product = new Product();
        $configProduct = new Product();
        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->getParentProduct($configProduct);
        $product->getParentVariantLinks(new ArrayCollection([$configProduct]));

        $this->featureChecker->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('simple_variations_view_restriction')
            ->willReturn(true);

        $result = $this->extension->getVisibleProduct($lineItem);
        $this->assertEquals($configProduct, $result);
    }
}
