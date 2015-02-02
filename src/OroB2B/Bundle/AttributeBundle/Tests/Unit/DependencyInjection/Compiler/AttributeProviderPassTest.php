<?php
namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\DependencyInjection\Compiler;

use OroB2B\Bundle\AttributeBundle\DependencyInjection\Compiler\AttributeProviderPass;
use Symfony\Component\DependencyInjection\Reference;

class AttributeProviderPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\DependencyInjection\ContainerBuilder
     */
    private $container;

    /**
     * Environment setup
     */
    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')->getMock();
    }

    public function testProcessNotRegisterProvider()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo('orob2b_attribute.attribute_type.registry'))
            ->will($this->returnValue(false));

        $this->container->expects($this->never())
            ->method('getDefinition');
        $this->container->expects($this->never())
            ->method('findTaggedServiceIds');

        $compilerPass = new AttributeProviderPass();
        $compilerPass->process($this->container);
    }

    public function testProcess()
    {
        $registryDefinition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $registryDefinition->expects($this->at(0))
            ->method('addMethodCall')
            ->with($this->equalTo('addType'), $this->equalTo([new Reference('service1')]));
        $registryDefinition->expects($this->at(1))
            ->method('addMethodCall')
            ->with($this->equalTo('addType'), $this->equalTo([new Reference('service2')]));

        $service1Definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $service1Definition->expects($this->once())
            ->method('setPublic')
            ->will($this->returnValue(false));

        $service2Definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->getMock();
        $service2Definition->expects($this->once())
            ->method('setPublic')
            ->will($this->returnValue(false));

        $serviceIds = [
            'service1' => [],
            'service2' => []
        ];

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with($this->equalTo('orob2b_attribute.attribute_type.registry'))
            ->will($this->returnValue(true));

        $this->container->expects($this->exactly(3))
            ->method('getDefinition')
            ->will(
                $this->returnValueMap(
                    [
                        ['orob2b_attribute.attribute_type.registry', $registryDefinition],
                        ['service1', $service1Definition],
                        ['service2', $service2Definition],
                    ]
                )
            );

        $this->container->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with($this->equalTo('orob2b_attribute.attribute_type'))
            ->will($this->returnValue($serviceIds));

        $compilerPass = new AttributeProviderPass();
        $compilerPass->process($this->container);
    }
}
