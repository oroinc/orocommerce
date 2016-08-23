<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ProductDataStorageSessionBagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProductDataStorageSessionBagPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductDataStorageSessionBagPass
     */
    protected $compilerPass;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $container;

    protected function setUp()
    {
        $this->container = $this
            ->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $this->compilerPass = new ProductDataStorageSessionBagPass();
    }

    public function testSessionNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo('session'))
            ->will($this->returnValue(false));

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testBagServiceNotExists()
    {
        $this->container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    ['session', true],
                    ['orob2b_product.storage.product_data_bag', false],
                ]
            );

        $this->container->expects($this->never())
            ->method('getDefinition');

        $this->compilerPass->process($this->container);
    }

    public function testRegisterBag()
    {
        $this->container->expects($this->exactly(2))
            ->method('hasDefinition')
            ->willReturnMap(
                [
                    ['session', true],
                    ['orob2b_product.storage.product_data_bag', true],
                ]
            );

        $definition = $this->getMock('Symfony\Component\DependencyInjection\Definition');

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with($this->equalTo('session'))
            ->will($this->returnValue($definition));

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'registerBag',
                $this->callback(
                    function ($value) {
                        $this->assertInternalType('array', $value);
                        $this->assertArrayHasKey(0, $value);
                        /** @var Reference $reference */
                        $reference = $value[0];
                        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Reference', $reference);
                        $this->assertEquals('orob2b_product.storage.product_data_bag', (string)$reference);

                        return true;
                    }
                )
            );

        $this->compilerPass->process($this->container);
    }
}
