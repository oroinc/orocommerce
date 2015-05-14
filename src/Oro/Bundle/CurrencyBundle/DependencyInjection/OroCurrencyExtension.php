<?php

namespace Oro\Bundle\CurrencyBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Intl\Intl;

class OroCurrencyExtension extends Extension
{
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

        $this->prepareSettings($config, $container);
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function prepareSettings(array $config, ContainerBuilder $container)
    {
        if (empty($config['settings']['allowed_currencies']['value'])) {
            $locale = $container->getParameter('locale');
            $currencies = array_keys(Intl::getCurrencyBundle()->getCurrencyNames($locale));

            $config['settings']['allowed_currencies']['value'] = $currencies;
        }

        $container->prependExtensionConfig($this->getAlias(), $config);
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return 'oro_currency';
    }
}
