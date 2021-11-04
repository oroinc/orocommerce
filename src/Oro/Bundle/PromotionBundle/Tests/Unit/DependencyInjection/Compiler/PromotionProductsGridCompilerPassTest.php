<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionProductsGridCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PromotionProductsGridCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();
        $listenerDef = $container->register('oro_product.event_listener.product_collection_datagrid');

        $compiler = new PromotionProductsGridCompilerPass();
        $compiler->process($container);

        self::assertEquals(
            [
                'kernel.event_listener' => [
                    [
                        'event'  => 'oro_datagrid.datagrid.build.after.promotion-products-collection-grid',
                        'method' => 'onBuildAfter'
                    ]
                ]
            ],
            $listenerDef->getTags()
        );
    }
}
