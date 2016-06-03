<?php

namespace OroB2B\Bundle\MoneyOrderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class OroB2BMoneyOrderExtension extends Extension
{
    const ALIAS = 'orob2b_money_order';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->prependExtensionConfig($this->getAlias(), $config);
        
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('payment.yml');
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
