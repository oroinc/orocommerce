<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Twig;

use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\ShoppingListBundle\Twig\ShoppingListLimitExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ShoppingListLimitExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ShoppingListLimitManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListLimitManager;

    /** @var ShoppingListLimitExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);

        $container = self::getContainerBuilder()
            ->add('oro_shopping_list.manager.shopping_list_limit', $this->shoppingListLimitManager)
            ->getContainer($this);

        $this->extension = new ShoppingListLimitExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(ShoppingListLimitExtension::NAME, $this->extension->getName());
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
}
