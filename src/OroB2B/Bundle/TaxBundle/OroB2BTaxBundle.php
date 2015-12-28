<?php

namespace OroB2B\Bundle\TaxBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\TaxBundle\DependencyInjection\CompilerPass\TaxProviderPass;
use OroB2B\Bundle\TaxBundle\DependencyInjection\OroB2BTaxExtension;

class OroB2BTaxBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TaxProviderPass());
    }

    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroB2BTaxExtension();
    }
}
