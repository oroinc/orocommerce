<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\RfpProductCheckerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RfpProductCheckerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerBuilder
     */
    protected $container;

    /**
     * @var RfpProductCheckerPass
     */
    protected $rfpProductCheckerPass;

    protected function setUp()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container **/
        $this->container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->rfpProductCheckerPass = new RfpProductCheckerPass();
    }

    public function testRfpFormExtensionNotExists()
    {
        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_rfp.form.type.extension.frontend_request_data_storage')
            ->willReturn(false)
        ;

        $this->rfpProductCheckerPass->process($this->container);
    }

    public function testRfpFormExtensionExists()
    {
        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $securityPolicyDefinition */
        $definition = $this->getMockBuilder(Definition::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->container->expects($this->once())
            ->method('hasDefinition')
            ->with('oro_rfp.form.type.extension.frontend_request_data_storage')
            ->willReturn($definition)
        ;

        $this->container->expects($this->once())
            ->method('getDefinition')
            ->with('oro_rfp.form.type.extension.frontend_request_data_storage')
            ->willReturnMap([
                [
                    'oro_rfp.form.type.extension.frontend_request_data_storage',
                    $definition
                ]
            ])
        ;

        $this->rfpProductCheckerPass->process($this->container);
    }
}
