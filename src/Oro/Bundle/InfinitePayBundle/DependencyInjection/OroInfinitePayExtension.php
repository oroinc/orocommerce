<?php

namespace Oro\Bundle\InfinitePayBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroInfinitePayExtension extends Extension
{
    const ALIAS = 'oro_infinite_pay';

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('parameters.yml');
        $loader->load('services.yml');
        $loader->load('method.yml');
        $loader->load('actions.yml');
        $loader->load('action_mappers.yml');
        $loader->load('form_types.yml');
        $loader->load('request_providers.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return static::ALIAS;
    }
}
