<?php

namespace Oro\Bundle\WebsiteSearchBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler\WebsiteSearchProviderPass;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\OroWebsiteSearchExtension;

class WebsiteSearchBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new WebsiteSearchProviderPass());
    }

    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroWebsiteSearchExtension();
    }
}
