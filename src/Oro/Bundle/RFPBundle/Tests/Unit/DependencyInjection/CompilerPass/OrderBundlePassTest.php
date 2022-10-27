<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\RFPBundle\DependencyInjection\CompilerPass\OrderBundlePass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OrderBundlePassTest extends \PHPUnit\Framework\TestCase
{
    /** @var CompilerPassInterface */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new OrderBundlePass();
    }

    public function testProcessWithOrderBundleWithoutDefinition()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcessWithOrderBundle()
    {
        $container = new ContainerBuilder();
        $container->register('oro_order.form.section.provider');
        $storageDef = $container->register('oro_rfp.form.type.extension.order_line_item_data_storage');

        $this->compiler->process($container);

        $this->assertEquals(
            [
                ['setSectionProvider', [new Reference('oro_order.form.section.provider')]]
            ],
            $storageDef->getMethodCalls()
        );
    }
}
