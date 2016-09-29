<?php

namespace Oro\Bundle\InfinitePayBundle;

use Oro\Bundle\InfinitePayBundle\DependencyInjection\Compiler\ActionsCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroInfinitePayBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ActionsCompilerPass());
        parent::build($container);
    }
}
