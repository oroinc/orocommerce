<?php

namespace Oro\Bundle\RedirectBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class OroRedirectExtension extends Extension
{
    const ALIAS = 'oro_redirect';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_types.yml');

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
        $this->addClassesToCompile(
            [
                'Oro\Bundle\RedirectBundle\Security\Firewall',
                'Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher',
                'Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator',
                'Oro\Bundle\RedirectBundle\Routing\Router'
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
