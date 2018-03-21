<?php

namespace Oro\Bundle\WebsiteSearchBundle;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchCompilerPass;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchTypeProviderCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroWebsiteSearchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new WebsiteSearchCompilerPass());
        $container->addCompilerPass(new WebsiteSearchTypeProviderCompilerPass());
    }
}
