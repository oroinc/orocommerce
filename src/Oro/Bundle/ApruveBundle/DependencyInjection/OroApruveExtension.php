<?php

namespace Oro\Bundle\ApruveBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroApruveExtension extends Extension
{
    const ALIAS = 'oro_apruve';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('integration.yml');
        $loader->load('form_types.yml');
        $loader->load('method.yml');
        $loader->load('payment_action.yml');
        $loader->load('apruve.yml');
        $loader->load('listeners.yml');
        $loader->load('client.yml');
        $loader->load('connection.yml');
        $loader->load('handlers.yml');
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
