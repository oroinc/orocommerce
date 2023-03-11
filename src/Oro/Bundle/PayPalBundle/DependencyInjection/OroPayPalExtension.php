<?php

namespace Oro\Bundle\PayPalBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroPayPalExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->setParameter(
            Configuration::getConfigKey(Configuration::CONFIG_KEY_ALLOWED_IPS),
            $config[Configuration::CONFIG_KEY_ALLOWED_IPS]
        );

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_types.yml');
        $loader->load('payflow.yml');
        $loader->load('method.yml');
        $loader->load('listeners.yml');

        if ('test' === $container->getParameter('kernel.environment')) {
            $loader->load('payment_test.yml');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias(): string
    {
        return Configuration::ROOT_NODE;
    }
}
