<?php

namespace Oro\Bundle\RuleBundle\Tests;

use Oro\Bundle\RuleBundle\DependencyInjection\CompilerPass\ExpressionLanguageFunctionCompilerPass;
use Oro\Bundle\RuleBundle\OroRuleBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroRuleBundleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $containerBuilderMock;

    public function setUp()
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
