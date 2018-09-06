<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\LayoutBlockOptionsCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionProductsGridCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\OroPromotionExtension;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\PromotionBundle\OroPromotionBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class OroPromotionBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testBuild()
    {
        $container = new ContainerBuilder();

        $kernel = $this->createMock(KernelInterface::class);

        $bundle = new OroPromotionBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();

        $this->assertInternalType('array', $passes);
        $this->assertCount(5, $passes);

        $this->assertInstanceOf(DefaultFallbackExtensionPass::class, $passes[0]);
        $this->assertAttributeEquals(
            [
                Promotion::class => [
                    'label' => 'labels',
                    'description' => 'descriptions',
                ]
            ],
            'classes',
            $passes[0]
        );

        $expectedPasses = [
            new PromotionCompilerPass(),
            new PromotionProductsGridCompilerPass(),
            new LayoutBlockOptionsCompilerPass(),
            new TwigSandboxConfigurationPass()
        ];

        foreach ($expectedPasses as $expectedPass) {
            $this->assertContains($expectedPass, $passes, '', false, false);
        }
    }

    public function testGetContainerExtension()
    {
        $bundle = new OroPromotionBundle();

        $this->assertInstanceOf(OroPromotionExtension::class, $bundle->getContainerExtension());
    }
}
