<?php

namespace OroB2B\Bundle\TaxBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use OroB2B\Bundle\FrontendBundle\DependencyInjection\Loader\PrivateYamlFileLoader;

class OroB2BTaxExtension extends Extension
{
    const ALIAS = 'orob2b_tax';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->prependExtensionConfig($this->getAlias(), $config);

        $loader = new PrivateYamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_types.yml');
    }

    /** {@inheritdoc} */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
