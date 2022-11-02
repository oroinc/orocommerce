<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue as InventoryStatus;
use Oro\Bundle\ProductBundle\Controller\Frontend\BrandController;
use Oro\Bundle\ProductBundle\Controller\Frontend\ProductController;
use Oro\Bundle\ProductBundle\EventListener\RestrictProductViewByInventoryStatusListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RestrictProductViewByInventoryStatusListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var RestrictProductViewByInventoryStatusListener */
    private $listener;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new RestrictProductViewByInventoryStatusListener($this->configManager);
    }

    /**
     * @dataProvider eventDataProvider
     *
     * @param callable $controller
     * @param Product|null $product
     * @param string|null $inventoryStatusCode
     */
    public function testNoRestriction($controller, Product $product = null, string $inventoryStatusCode = null): void
    {
        if ($inventoryStatusCode) {
            $inventoryStatus = new InventoryStatus($inventoryStatusCode, $inventoryStatusCode);
            if ($product) {
                $product->setInventoryStatus($inventoryStatus);
            }
        }
        $allowedStatuses = ['in_stock'];

        $request = Request::create('/product/view/1', 'GET', []);
        $request->attributes->set('product', $product);

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_product.general_frontend_product_visibility')
            ->willReturn($allowedStatuses);

        $this->listener->onKernelController($event);
    }

    public function eventDataProvider(): array
    {
        $productController = $this->createMock(ProductController::class);

        return [
            'unsupported controller' => [
                [$this->createMock(BrandController::class), 'indexAction'],
                $this->getEntity(Product::class),
                'in_stock'
            ],
            'unsupported action' => [
                [$productController, 'indexAction'],
                $this->getEntity(Product::class),
                'in_stock'
            ],
            'no product' => [[$productController, 'viewAction'], null, null],
            'allowed status' => [[$productController, 'viewAction'], $this->getEntity(Product::class), 'in_stock'],
        ];
    }

    public function testRestriction(): void
    {
        $inventoryStatus = new InventoryStatus('out_of_stock', 'out_of_stock');
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $product->setInventoryStatus($inventoryStatus);
        $allowedStatuses = ['in_stock'];

        $request = Request::create('/product/view/1', 'GET', []);
        $request->attributes->set('product', $product);

        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [$this->createMock(ProductController::class), 'viewAction'],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->configManager->expects(self::any())
            ->method('get')
            ->with('oro_product.general_frontend_product_visibility')
            ->willReturn($allowedStatuses);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Inventory status "out_of_stock" is configured as invisible. Product id: 42');

        $this->listener->onKernelController($event);
    }
}
