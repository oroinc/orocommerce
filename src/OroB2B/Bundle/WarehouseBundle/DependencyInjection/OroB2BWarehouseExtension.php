<?php

namespace OroB2B\Bundle\WarehouseBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class OroB2BWarehouseExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $bundles = $container->getParameter('kernel.bundles');
        if (array_key_exists('OroB2BOrderBundle', $bundles)) {
            $loader->load('order_services.yml');
        }

        $loader->load('services.yml');
        $loader->load('form_types.yml');
    }
}
