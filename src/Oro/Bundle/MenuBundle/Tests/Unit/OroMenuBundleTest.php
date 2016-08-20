<?php

namespace Oro\Bundle\MenuBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\MenuBundle\Entity\MenuItem;
use Oro\Bundle\MenuBundle\DependencyInjection\Compiler\ConditionExpressionLanguageProvidersCompilerPass;
use Oro\Bundle\MenuBundle\OroMenuBundle;

class OroMenuBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle = new OroMenuBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        $this->assertInternalType('array', $passes);
        $this->assertCount(2, $passes);
        $this->assertInstanceOf(ConditionExpressionLanguageProvidersCompilerPass::class, $passes[0]);
        $this->assertInstanceOf(DefaultFallbackExtensionPass::class, $passes[1]);
        $this->assertAttributeEquals(
            [
                MenuItem::class => [
                    'title' => 'titles'
                ]
            ],
            'classes',
            $passes[1]
        );
    }
}
