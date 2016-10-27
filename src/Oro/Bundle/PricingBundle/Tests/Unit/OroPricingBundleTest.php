<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\ProductExpressionServicesPass;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\SubtotalProviderPass;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\PricingBundle\OroPricingBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroPricingBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContainerExtension()
    {
        $bundle = new OroPricingBundle();
        $this->assertInstanceOf(OroPricingExtension::class, $bundle->getContainerExtension());
    }

    public function testBuild()
    {
        $bundle = new OroPricingBundle();
        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->exactly(2))
            ->method('addCompilerPass')
            ->withConsecutive(
                [$this->isInstanceOf(SubtotalProviderPass::class)],
                [$this->isInstanceOf(ProductExpressionServicesPass::class)]
            );
        $bundle->build($container);
    }
}
