<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\DiscountContextConverterCompilerPass;
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
        $this->assertCount(1, $passes);

        $expectedPasses = [
            new DiscountContextConverterCompilerPass()
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
