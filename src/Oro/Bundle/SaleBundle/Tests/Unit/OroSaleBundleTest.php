<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit;

use Oro\Bundle\SaleBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\SaleBundle\OroSaleBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroSaleBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild(): void
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(TwigSandboxConfigurationPass::class));

        $bundle = new OroSaleBundle();
        $bundle->build($container);
    }
}
