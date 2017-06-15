<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\DiscountContextConverterCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionContextConverterCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\OroPromotionExtension;
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
        $this->assertCount(2, $passes);

        $expectedPasses = [
            new DiscountContextConverterCompilerPass(),
            new PromotionContextConverterCompilerPass()
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
