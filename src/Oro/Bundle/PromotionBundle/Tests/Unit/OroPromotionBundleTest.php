<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
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

        $this->assertCount(1, $passes);
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
    }
}
