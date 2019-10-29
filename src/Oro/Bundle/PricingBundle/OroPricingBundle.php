<?php

namespace Oro\Bundle\PricingBundle;

use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\DisableDataAuditListenerPass;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\PricesStrategyPass;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\ProductExpressionServicesPass;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\SubtotalProviderPass;
use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The PricingBundle bundle class.
 */
class OroPricingBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroPricingExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new DisableDataAuditListenerPass());
        $container->addCompilerPass(new SubtotalProviderPass());
        $container->addCompilerPass(new ProductExpressionServicesPass());
        $container->addCompilerPass(new PricesStrategyPass());
    }
}
