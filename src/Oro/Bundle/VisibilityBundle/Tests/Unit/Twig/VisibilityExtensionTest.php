<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Twig;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use Oro\Bundle\VisibilityBundle\Twig\VisibilityExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class VisibilityExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private ResolvedProductVisibilityProvider&MockObject $resolvedProductVisibilityProvider;
    private VisibilityExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);

        $container = self::getContainerBuilder()
            ->add(ResolvedProductVisibilityProvider::class, $this->resolvedProductVisibilityProvider)
            ->getContainer($this);

        $this->extension = new VisibilityExtension($container);
    }

    public function testIsVisibleProductWhenArgumentIsNull(): void
    {
        $this->resolvedProductVisibilityProvider->expects(self::never())
            ->method('isVisible')
            ->willReturn(false);

        self::assertFalse(self::callTwigFunction($this->extension, 'is_visible_product', [null]));
    }

    public function testIsVisibleProductWhenArgumentIsNewProduct(): void
    {
        $this->resolvedProductVisibilityProvider->expects(self::never())
            ->method('isVisible')
            ->willReturn(false);

        self::assertFalse(self::callTwigFunction($this->extension, 'is_visible_product', [new Product()]));
    }

    public function testIsVisibleProductWhenArgumentIsProduct(): void
    {
        $product = (new ProductStub())->setId(42);

        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('isVisible')
            ->with($product->getId())
            ->willReturn(true);

        self::assertTrue(self::callTwigFunction($this->extension, 'is_visible_product', [$product]));
    }

    public function testIsVisibleProductWhenArgumentIsInt(): void
    {
        $productId = 42;
        $this->resolvedProductVisibilityProvider->expects(self::once())
            ->method('isVisible')
            ->with($productId)
            ->willReturn(true);

        self::assertTrue(self::callTwigFunction($this->extension, 'is_visible_product', [$productId]));
    }
}
