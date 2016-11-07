<?php

namespace Oro\Bundle\RedirectBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class OroRedirectExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->addClassesToCompile(
            [
                'Oro\Bundle\RedirectBundle\Security\Firewall',
                'Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher'
            ]
        );
    }
}
