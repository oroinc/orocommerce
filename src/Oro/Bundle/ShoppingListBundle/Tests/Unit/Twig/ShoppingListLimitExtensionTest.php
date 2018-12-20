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

    protected function setUp()
    {
        $this->shoppingListLimitManager = $this->getMockBuilder(ShoppingListLimitManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new ShoppingListLimitExtension($this->shoppingListLimitManager);
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
