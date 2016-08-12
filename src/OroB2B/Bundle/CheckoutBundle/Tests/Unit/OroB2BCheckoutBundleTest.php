<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use OroB2B\Bundle\CheckoutBundle\OroB2BCheckoutBundle;

class OroB2BCheckoutBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerBuilder');
        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $container->expects($this->exactly(3))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf('Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface'))
            ->will($this->returnSelf());

        $bundle = new OroB2BCheckoutBundle($kernel);
        $bundle->build($container);
    }
}
