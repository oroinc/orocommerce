<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InventoryBundle\Tests\Unit\Inventory\Stub\InventoryStatusStub;
use Oro\Bundle\ProductBundle\Controller\Frontend\ProductController;
use Oro\Bundle\ProductBundle\EventListener\RestrictProductViewByInventoryStatusListener;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RestrictProductViewByInventoryStatusListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var RestrictProductViewByInventoryStatusListener
     */
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
    public function testNoRestriction($controller, Product $product = null, $inventoryStatusCode = null)
    {
        if ($inventoryStatusCode) {
            $inventoryStatus = new InventoryStatusStub($inventoryStatusCode, $inventoryStatusCode);
            if ($product) {
                $product->setInventoryStatus($inventoryStatus);
            }
        }
        $allowedStatuses = ['in_stock'];

        $request = Request::create('/product/view/1', 'GET', []);
        $request->attributes->set('product', $product);

        /** @var ControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ControllerEvent::class);
        $event->expects($this->any())
            ->method('getController')
            ->willReturn($controller);

        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_product.general_frontend_product_visibility')
            ->willReturn($allowedStatuses);

        $this->listener->onKernelController($event);
    }

    /**
     * @return array
     */
    public function eventDataProvider()
    {
        $productController = $this->createMock(ProductController::class);

        return [
            'no controller' => [null, $this->getEntity(Product::class), 'in_stock'],
            'unsupported controller' => [[new \stdClass(), 'test'], $this->getEntity(Product::class), 'in_stock'],
            'unsupported action' => [
                [$productController, 'createAction'],
                $this->getEntity(Product::class),
                'in_stock'
            ],
            'no product' => [[$productController, 'viewAction'], null, null],
            'allowed status' => [[$productController, 'viewAction'], $this->getEntity(Product::class), 'in_stock'],
        ];
    }

    public function testRestriction()
    {
        $inventoryStatus = new InventoryStatusStub('out_of_stock', 'out_of_stock');
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $product->setInventoryStatus($inventoryStatus);
        $allowedStatuses = ['in_stock'];

        $request = Request::create('/product/view/1', 'GET', []);
        $request->attributes->set('product', $product);

        /** @var ControllerEvent|\PHPUnit\Framework\MockObject\MockObject $event */
        $event = $this->createMock(ControllerEvent::class);
        $event->expects($this->any())
            ->method('getController')
            ->willReturn([$this->createMock(ProductController::class), 'viewAction']);

        $event->expects($this->any())
            ->method('getRequest')
            ->willReturn($request);

        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_product.general_frontend_product_visibility')
            ->willReturn($allowedStatuses);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Inventory status "out_of_stock" is configured as invisible. Product id: 42');

        $this->listener->onKernelController($event);
    }
}
