<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Twig;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Layout\DataProvider\LayoutButtonProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Oro\Bundle\ShoppingListBundle\Twig\ShoppingListExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ShoppingListExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ShoppingListLimitManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListLimitManager;

    /** @var ShoppingListUrlProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListUrlProvider;

    /** @var LayoutButtonProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $layoutButtonProvider;

    /** @var ShoppingListExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);
        $this->shoppingListUrlProvider = $this->createMock(ShoppingListUrlProvider::class);
        $this->layoutButtonProvider = $this->createMock(LayoutButtonProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_shopping_list.manager.shopping_list_limit', $this->shoppingListLimitManager)
            ->add(ShoppingListUrlProvider::class, $this->shoppingListUrlProvider)
            ->add('oro_action.layout.data_provider.button_provider', $this->layoutButtonProvider)
            ->getContainer($this);

        $this->extension = new ShoppingListExtension($container);
    }

    public function testIsConfigurableSimple()
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

    public function testGetShoppingListWidgetButtons()
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
}
