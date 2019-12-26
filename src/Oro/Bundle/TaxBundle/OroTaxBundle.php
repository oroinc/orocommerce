<?php

namespace Oro\Bundle\TaxBundle;

use Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass\AddressMatcherRegistryPass;
use Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass\ResolverEventConnectorPass;
use Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass\TaxMapperPass;
use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedServiceViaAddMethodCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The TaxBundle bundle class.
 */
class OroTaxBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PriorityTaggedServiceViaAddMethodCompilerPass(
            'oro_tax.provider.tax_provider_registry',
            'addProvider',
            'oro_tax.tax_provider'
        ));
        $container->addCompilerPass(new TaxMapperPass());
        $container->addCompilerPass(new ResolverEventConnectorPass());
        $container->addCompilerPass(new AddressMatcherRegistryPass());
    }

    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroTaxExtension();
    }
}
