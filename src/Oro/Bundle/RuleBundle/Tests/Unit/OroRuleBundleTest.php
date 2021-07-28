<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit;

use Oro\Bundle\RuleBundle\DependencyInjection\CompilerPass\ExpressionLanguageFunctionCompilerPass;
use Oro\Bundle\RuleBundle\OroRuleBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroRuleBundleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $containerBuilderMock;

    protected function setUp(): void
    {
        $this->containerBuilderMock = $this->createMock(ContainerBuilder::class);
    }

    public function testBuild()
    {
        $this->containerBuilderMock
            ->expects($this->once())
            ->method('addCompilerPass')
            ->with(new ExpressionLanguageFunctionCompilerPass());

        $bundle = new OroRuleBundle();
        $bundle->build($this->containerBuilderMock);
    }
}
