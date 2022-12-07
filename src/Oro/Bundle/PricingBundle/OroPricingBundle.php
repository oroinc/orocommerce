<?php

namespace Oro\Bundle\PricingBundle;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\ExportCategoryFilterPass;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\PricesStrategyPass;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\ProductExpressionServicesPass;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\SubtotalProviderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroPricingBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SubtotalProviderPass());
        $container->addCompilerPass(new ProductExpressionServicesPass());
        $container->addCompilerPass(new PricesStrategyPass());
        $container->addCompilerPass(new ExportCategoryFilterPass());
    }
}
