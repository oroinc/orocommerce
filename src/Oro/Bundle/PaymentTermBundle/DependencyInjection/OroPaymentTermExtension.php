<?php

namespace Oro\Bundle\PaymentTermBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroPaymentTermExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('form_types.yml');
        $loader->load('payment.yml');
        $loader->load('services.yml');
        $loader->load('integration.yml');
        $loader->load('controllers.yml');
    }
}
