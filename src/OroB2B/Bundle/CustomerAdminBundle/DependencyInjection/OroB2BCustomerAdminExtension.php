<?php

namespace OroB2B\Bundle\CustomerAdminBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;

class OroB2BCustomerAdminExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_types.yml');
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return 'oro_b2b_customer_admin';
    }
}
