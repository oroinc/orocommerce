<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\RuleBundle\DependencyInjection\CompilerPass\ExpressionLanguageFunctionCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExpressionLanguageFunctionCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExpressionLanguageFunctionCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new ExpressionLanguageFunctionCompilerPass();
    }

    public function testProcessNotExpressionLanguageService()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $expressionLanguageDef = $container->register('oro_rule.expression_language');

        $container->register('function_service_1')
            ->addTag('oro_rule.expression_language.function');
        $container->register('function_service_2')
            ->addTag('oro_rule.expression_language.function');

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addFunction', [new Reference('function_service_1')]],
                ['addFunction', [new Reference('function_service_2')]]
            ],
            $expressionLanguageDef->getMethodCalls()
        );
    }

    public function testProcessNoTagged()
    {
        $container = new ContainerBuilder();
        $expressionLanguageDef = $container->register('oro_rule.expression_language');

        $this->compiler->process($container);

        self::assertSame([], $expressionLanguageDef->getMethodCalls());
    }
}
