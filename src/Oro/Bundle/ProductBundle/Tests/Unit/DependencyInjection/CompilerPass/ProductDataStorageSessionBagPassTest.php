<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductDataStorageSessionBagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProductDataStorageSessionBagPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductDataStorageSessionBagPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ProductDataStorageSessionBagPass();
    }

    public function testSessionNotExists()
    {
        $container = new ContainerBuilder();
        $container->register('oro_product.storage.product_data_bag');

        $this->compiler->process($container);
    }

    public function testBagServiceNotExists()
    {
        $container = new ContainerBuilder();
        $sessionDef = $container->register('session');

        $this->compiler->process($container);

        self::assertSame([], $sessionDef->getMethodCalls());
    }

    public function testRegisterBag()
    {
        $container = new ContainerBuilder();
        $sessionDef = $container->register('session');
        $container->register('oro_product.storage.product_data_bag');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['registerBag', [new Reference('oro_product.storage.product_data_bag')]]
            ],
            $sessionDef->getMethodCalls()
        );
    }
}
