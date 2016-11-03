<?php

namespace Oro\Bundle\RedirectBundle;

use Oro\Bundle\RedirectBundle\DependencyInjection\Compiler\RoutingCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroRedirectBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RoutingCompilerPass());
    }
}
