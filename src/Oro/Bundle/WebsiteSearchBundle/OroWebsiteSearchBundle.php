<?php

namespace Oro\Bundle\WebsiteSearchBundle;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchCompilerPass;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroWebsiteSearchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new WebsiteSearchCompilerPass());
        parent::build($container);
    }
}
