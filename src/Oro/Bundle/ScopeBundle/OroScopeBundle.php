<?php

namespace Oro\Bundle\ScopeBundle;

use Oro\Bundle\ScopeBundle\DependencyInjection\Compiler\ScopeProviderCompiler;
use Oro\Bundle\ScopeBundle\DependencyInjection\OroScopeExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroScopeBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ScopeProviderCompiler());
    }

    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroScopeExtension();
    }
}
