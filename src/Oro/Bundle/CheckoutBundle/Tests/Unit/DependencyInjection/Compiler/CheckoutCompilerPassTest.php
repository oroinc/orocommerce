<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler\CheckoutCompilerPass;

class CheckoutCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider processDataProvider
     * @param bool $issetDefinition
     */
    public function testProcess($issetDefinition)
    {
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerBuilder */
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $containerBuilder->expects($this->once())
            ->method('has')
            ->with(CheckoutCompilerPass::CHECKOUT_DATA_PROVIDER_MANAGER)
            ->willReturn($issetDefinition);

        if ($issetDefinition) {
            $containerBuilder->expects($this->once())
                ->method('getDefinition')
                ->with(CheckoutCompilerPass::CHECKOUT_DATA_PROVIDER_MANAGER)
                ->will($this->returnValue($definition));

            $services = array('testId' => array());

            $containerBuilder->expects($this->once())
                ->method('findTaggedServiceIds')
                ->with(CheckoutCompilerPass::CHECKOUT_DATA_PROVIDER_TAG)
                ->will($this->returnValue($services));

            $definition->expects($this->once())
                ->method('addMethodCall')
                ->with('addProvider', array(new Reference('testId')));
        }

        $pass = new CheckoutCompilerPass();
        $pass->process($containerBuilder);
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            'with checkout manager definition' => [
                'issetDefinition' => true
            ],
            'without checkout manager definition' => [
                'issetDefinition' => false
            ]
        ];
    }
}
