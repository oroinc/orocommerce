<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit;

use Oro\Bundle\CheckoutBundle\OroCheckoutBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCheckoutBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject $container */
        $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $kernel = $this->createMock('Symfony\Component\HttpKernel\KernelInterface');

        $container->expects($this->exactly(4))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface'))
            ->will($this->returnSelf());

        $bundle = new OroCheckoutBundle($kernel);
        $bundle->build($container);
    }
}
