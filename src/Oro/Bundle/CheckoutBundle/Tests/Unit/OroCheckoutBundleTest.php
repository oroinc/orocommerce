<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\CheckoutBundle\OroCheckoutBundle;

class OroCheckoutBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $container->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface'))
            ->will($this->returnSelf());

        $bundle = new OroCheckoutBundle($kernel);
        $bundle->build($container);
    }
}
