<?php

namespace Oro\Bundle\FrontendNavigationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;

class OroFrontendNavigationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(
            new DefaultFallbackExtensionPass([
                'Oro\Bundle\FrontendNavigationBundle\Entity\MenuUpdate' => ['title' => 'titles']
            ])
        );
    }
}
