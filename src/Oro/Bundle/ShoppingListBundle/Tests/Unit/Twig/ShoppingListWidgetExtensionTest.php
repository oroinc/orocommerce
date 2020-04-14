<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Twig;

use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Layout\DataProvider\LayoutButtonProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Twig\ShoppingListWidgetExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ShoppingListWidgetExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /**
     * @var LayoutButtonProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $provider;

    /**
     * @var ShoppingListWidgetExtension
     */
    private $extension;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(LayoutButtonProvider::class);
        $container = self::getContainerBuilder()
            ->add('oro_action.layout.data_provider.button_provider', $this->provider)
            ->getContainer($this);

        $this->extension = new ShoppingListWidgetExtension($container);
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
        $this->provider->expects($this->once())
            ->method('getAll')
            ->with($shoppingList)
            ->willReturn([$button1, $button2]);

        self::assertEquals(
            [$button1],
            self::callTwigFunction($this->extension, 'get_shopping_list_widget_buttons', [$shoppingList])
        );
    }
}
