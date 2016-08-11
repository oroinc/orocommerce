<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\DependencyInjection\Compiler\ConditionExpressionLanguageProvidersCompilerPass;
use OroB2B\Bundle\MenuBundle\OroB2BMenuBundle;

class OroB2BMenuBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');

        $bundle = new OroB2BMenuBundle($kernel);
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
