<?php

namespace Oro\Bundle\VisibilityBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\SecurityBundle\DependencyInjection\Extension\SecurityExtensionHelper;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroVisibilityExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->prependExtensionConfig($this->getAlias(), SettingsBuilder::getSettings($config));

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('services_api.yml');
        $loader->load('form_types.yml');
        $loader->load('commands.yml');
        $loader->load('controllers.yml');
        $loader->load('website_search.yml');
        $loader->load('mq_topics.yml');
        $loader->load('mq_processors.yml');
    }

    /**
     * {@inheritDoc}
     */
    public function prepend(ContainerBuilder $container): void
    {
        /** @var ExtendedContainerBuilder $container */
        SecurityExtensionHelper::makeFirewallLatest($container, 'frontend_secure');
        SecurityExtensionHelper::makeFirewallLatest($container, 'frontend');
    }
}
