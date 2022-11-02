<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Controller\Frontend\BrandController;
use Oro\Bundle\ProductBundle\Controller\Frontend\ProductController;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\EventListener\RestrictVariantProductViewListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RestrictVariantProductViewListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private FeatureChecker|MockObject $featureChecker;
    private RestrictVariantProductViewListener $listener;

    protected function setUp(): void
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new RestrictVariantProductViewListener();
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('simple_variations_view_restriction');
    }

    /**
     * @dataProvider eventDataProvider
     */
    public function testNoRestriction(
        ?array $controller,
        bool $isFeatureEnabled,
        ?Product $product,
        bool $isLayoutRequest
    ): void {
        if ($isLayoutRequest) {
            $request = Request::create('/product/view/1', 'GET', ['layout_block_ids' => ['test']]);
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        } else {
            $request = Request::create('/product/view/1', 'GET', []);
        }
        $request->attributes->set('product', $product);

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn($isFeatureEnabled);

        $this->listener->onKernelController($event);
    }

    public function eventDataProvider(): \Generator
    {
        $productController = $this->createMock(ProductController::class);

        $parentVariantLink = new ProductVariantLink();
        /** @var Product $productVariant */
        $productVariant = $this->getEntity(Product::class, ['id' => 42]);
        $productVariant->addParentVariantLink($parentVariantLink);

        yield 'unsupported controller' => [
            [$this->createMock(BrandController::class), 'indexAction'],
            true,
            $productVariant,
            false
        ];

        yield 'unsupported action' => [
            [$productController, 'indexAction'],
            true,
            $productVariant,
            false
        ];

        yield 'no product' => [[$productController, 'viewAction'], true, null, false];

        yield 'restriction is disabled' => [[$productController, 'viewAction'], false, $productVariant, false];

        yield 'is layout subtree request' => [[$productController, 'viewAction'], true, $productVariant, true];
    }

    public function testRestriction(): void
    {
        $parentVariantLink = new ProductVariantLink();
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $product->addParentVariantLink($parentVariantLink);

        $request = Request::create('/product/view/1', 'GET', []);
        $request->attributes->set('product', $product);

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [$this->createMock(ProductController::class), 'viewAction'],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->willReturn(true);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Product variant view is restricted by system config. Product id: 42');

        $this->listener->onKernelController($event);
    }
}
