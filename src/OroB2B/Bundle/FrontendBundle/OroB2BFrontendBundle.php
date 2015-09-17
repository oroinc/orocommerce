<?php

namespace OroB2B\Bundle\FrontendBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\FrontendBundle\DependencyInjection\Compiler\ActivityPlaceholderFilterPass;

class OroB2BFrontendBundle extends Bundle
{

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ActivityPlaceholderFilterPass());
    }
}
