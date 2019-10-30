<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\DisableDataAuditListenerPass;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\PricesStrategyPass;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\ProductExpressionServicesPass;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\SubtotalProviderPass;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\PricingBundle\OroPricingBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroPricingBundleTest extends \PHPUnit\Framework\TestCase
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
        $container->expects($this->exactly(4))
            ->method('addCompilerPass')
            ->withConsecutive(
                [$this->isInstanceOf(DisableDataAuditListenerPass::class)],
                [$this->isInstanceOf(SubtotalProviderPass::class)],
                [$this->isInstanceOf(ProductExpressionServicesPass::class)],
                [$this->isInstanceOf(PricesStrategyPass::class)]
            );
        $bundle->build($container);
    }
}
