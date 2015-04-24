<?php

namespace Oro\Bundle\ApplicationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ApplicationBundle\DependencyInjection\Compiler\ApplicationHostPass;
use Oro\Bundle\ApplicationBundle\DependencyInjection\Compiler\RouterPrefixPass;

class OroApplicationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ApplicationHostPass());
        $container->addCompilerPass(new RouterPrefixPass());
    }
}
