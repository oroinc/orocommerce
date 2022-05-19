<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle;

use Oro\Bundle\FrontendTestFrameworkBundle\DependencyInjection\Compiler\ClientCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroFrontendTestFrameworkBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ClientCompilerPass());
    }
}
