<?php

namespace Oro\Bundle\FrontendLocalizationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroFrontendLocalizationExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('block_types.yml');
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('layout.yml');
        $loader->load('controllers.yml');
    }
}
