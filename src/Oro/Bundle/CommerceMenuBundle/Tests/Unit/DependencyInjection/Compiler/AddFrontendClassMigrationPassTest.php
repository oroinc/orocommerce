<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Bundle\CommerceMenuBundle\DependencyInjection\Compiler\AddFrontendClassMigrationPass;

class AddFrontendClassMigrationPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(AddFrontendClassMigrationPass::FRONTEND_CLASS_MIGRATION_SERVICE_ID)
            ->willReturn(true);

        /** @var Definition|\PHPUnit_Framework_MockObject_MockObject $definition */
        $definition = $this->getMockBuilder(Definition::class)->disableOriginalConstructor()->getMock();
        $definition->expects($this->exactly(2))
            ->method('addMethodCall')
            ->willReturnMap([
                ['append', ['FrontendNavigation', 'CommerceMenu'], $definition],
                ['append', ['frontendnavigation', 'commercemenu'], $definition],
            ]);

        $container->expects($this->once())
            ->method('findDefinition')
            ->with(AddFrontendClassMigrationPass::FRONTEND_CLASS_MIGRATION_SERVICE_ID)
            ->willReturn($definition);

        $compilerPass = new AddFrontendClassMigrationPass();
        $compilerPass->process($container);
    }

    public function testProcessSkip()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $container->expects($this->once())
            ->method('hasDefinition')
            ->with(AddFrontendClassMigrationPass::FRONTEND_CLASS_MIGRATION_SERVICE_ID)
            ->willReturn(false);

        $container->expects($this->never())
            ->method('findDefinition');

        $compilerPass = new AddFrontendClassMigrationPass();
        $compilerPass->process($container);
    }
}
