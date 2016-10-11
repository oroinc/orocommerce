<?php

namespace Oro\Bundle\TaxBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass\ResolverEventConnectorPass;
use Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass\TaxProviderPass;
use Oro\Bundle\TaxBundle\DependencyInjection\CompilerPass\TaxMapperPass;
use Oro\Bundle\TaxBundle\DependencyInjection\OroTaxExtension;

class OroTaxBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TaxProviderPass());
        $container->addCompilerPass(new TaxMapperPass());
        $container->addCompilerPass(new ResolverEventConnectorPass());
    }

    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroTaxExtension();
    }
}
