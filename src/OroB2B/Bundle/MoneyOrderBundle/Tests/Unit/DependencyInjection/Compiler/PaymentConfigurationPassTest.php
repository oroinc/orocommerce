<?php

namespace OroB2B\Bundle\MoneyOrderBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Compiler\PaymentConfigurationPass;

class PaymentConfigurationPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentConfigurationPass
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
            ->disableOriginalConstructor()
            ->getMock();

        $this->definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->compilerPass = new PaymentConfigurationPass();
    }

    protected function tearDown()
    {
        unset($this->container, $this->compilerPass);
    }

    public function testProcessWhenDefinitionExists()
    {
        $index = 0;

        $this->container->expects($this->at($index))
            ->method('hasDefinition')
            ->with(PaymentConfigurationPass::PAYMENT_METHOD_REGISTRY_SERVICE)
            ->willReturn(true);

        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->at(++$index))
            ->method('getDefinition')
            ->with(PaymentConfigurationPass::PAYMENT_METHOD_REGISTRY_SERVICE)
            ->will($this->returnValue($definition));

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'addPaymentMethod',
                [new Reference(PaymentConfigurationPass::MONEY_ORDER_PAYMENT_METHOD_SERVICE)]
            );

        $this->container->expects($this->at(++$index))
            ->method('hasDefinition')
            ->with(PaymentConfigurationPass::PAYMENT_METHOD_VIEW_REGISTRY_SERVICE)
            ->willReturn(true);

        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->at(++$index))
            ->method('getDefinition')
            ->with(PaymentConfigurationPass::PAYMENT_METHOD_VIEW_REGISTRY_SERVICE)
            ->will($this->returnValue($definition));

        $definition->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'addPaymentMethodView',
                [new Reference(PaymentConfigurationPass::MONEY_ORDER_PAYMENT_METHOD_VIEW_SERVICE)]
            );

        $this->compilerPass->process($this->container);
    }

    public function testProcessWhenDefinitionNotExists()
    {
        $index = 0;

        $this->container->expects($this->at($index))
            ->method('hasDefinition')
            ->with(PaymentConfigurationPass::PAYMENT_METHOD_REGISTRY_SERVICE)
            ->willReturn(false);

        $this->container->expects($this->at(++$index))
            ->method('hasDefinition')
            ->with(PaymentConfigurationPass::PAYMENT_METHOD_VIEW_REGISTRY_SERVICE)
            ->willReturn(false);

        $this->compilerPass->process($this->container);
    }
}
