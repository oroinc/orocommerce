<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\LayoutBlockOptionsCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionProductsGridCompilerPass;
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

        $passesBeforeBuild = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        $bundle = new OroPromotionBundle($kernel);
        $bundle->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getBeforeOptimizationPasses();
        // Remove default passes from array
        $passes = array_values(array_filter($passes, function ($pass) use ($passesBeforeBuild) {
            return !in_array($pass, $passesBeforeBuild, true);
        }));

        $this->assertInternalType('array', $passes);
        $this->assertCount(4, $passes);

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
            new LayoutBlockOptionsCompilerPass()
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
