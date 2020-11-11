<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Twig;

use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Oro\Bundle\ShoppingListBundle\Twig\ShoppingListUrlExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class ShoppingListUrlExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var ShoppingListUrlProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListUrlProvider;

    /** @var ShoppingListUrlExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->shoppingListUrlProvider = $this->createMock(ShoppingListUrlProvider::class);

        $container = self::getContainerBuilder()
            ->add(ShoppingListUrlProvider::class, $this->shoppingListUrlProvider)
            ->getContainer($this);

        $this->extension = new ShoppingListUrlExtension($container);
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
}
