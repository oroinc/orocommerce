<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\RuleBundle\DependencyInjection\CompilerPass\ExpressionLanguageFunctionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ExpressionLanguageFunctionCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $containerBuilderMock;

    /**
     * @var Definition|\PHPUnit\Framework\MockObject\MockObject
     */
    private $definitionMock;

    protected function setUp(): void
    {
        $this->containerBuilderMock = $this->createMock(ContainerBuilder::class);
        $this->definitionMock = $this->createMock(Definition::class);
    }

    public function testProcess()
    {
        $this->containerBuilderMock
            ->expects($this->once())
            ->method('hasDefinition')
            ->with(ExpressionLanguageFunctionCompilerPass::EXPRESSION_LANGUAGE_SERVICE)
            ->willReturn(true);

        $this->containerBuilderMock
            ->expects($this->once())
            ->method('getDefinition')
            ->with(ExpressionLanguageFunctionCompilerPass::EXPRESSION_LANGUAGE_SERVICE)
            ->willReturn($this->definitionMock);

        $this->containerBuilderMock
            ->expects($this->once())
            ->method('findTaggedServiceIds')
            ->with(ExpressionLanguageFunctionCompilerPass::TAG)
            ->willReturn([
                'someId' => [],
                'someOtherId' => []
            ]);

        $this->definitionMock
            ->expects($this->at(0))
            ->method('addMethodCall')
            ->with('addFunction', [new Reference('someId')]);

        $this->definitionMock
            ->expects($this->at(1))
            ->method('addMethodCall')
            ->with('addFunction', [new Reference('someOtherId')]);

        $compilerPass = new ExpressionLanguageFunctionCompilerPass();
        $compilerPass->process($this->containerBuilderMock);
    }
}
