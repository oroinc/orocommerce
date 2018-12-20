<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionProductsGridCompilerPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class PromotionProductsGridCompilerPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);

        $productCollectionListenerDefinition = $this->createMock(Definition::class);
        $productCollectionListenerDefinition
            ->expects($this->once())
            ->method('addTag')
            ->with('kernel.event_listener', [
                'event' => 'oro_datagrid.datagrid.build.after.promotion-products-collection-grid',
                'method' => 'onBuildAfter'
            ]);

        $containerBuilder
            ->expects($this->any())
            ->method('getDefinition')
            ->willReturnMap([
                ['oro_product.event_listener.product_collection_datagrid', $productCollectionListenerDefinition]
            ]);

        $compilerPass = new PromotionProductsGridCompilerPass();
        $compilerPass->process($containerBuilder);
    }
}
