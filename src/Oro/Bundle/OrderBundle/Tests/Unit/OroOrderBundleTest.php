<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit;

use Oro\Bundle\OrderBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\OrderBundle\OroOrderBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroOrderBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['addCompilerPass'])
            ->getMock();

        $containerBuilder->expects($this->once())
            ->method('addCompilerPass')
            ->with(
                $this->isInstanceOf(TwigSandboxConfigurationPass::class)
            );

        $bundle = new OroOrderBundle();
        $bundle->build($containerBuilder);
    }
}
