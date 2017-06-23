<?php

namespace Oro\Bundle\FrontendBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

use Oro\Bundle\LocaleBundle\DependencyInjection\OroLocaleExtension;
use Oro\Component\DependencyInjection\ExtendedContainerBuilder;

class OroFrontendExtension extends Extension implements PrependExtensionInterface
{
    const ALIAS = 'oro_frontend';
    
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
        $loader->load('form_type.yml');
        $loader->load('block_types.yml');

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container instanceof ExtendedContainerBuilder) {
            $configs = $container->getExtensionConfig('fos_rest');
            foreach ($configs as $configKey => $config) {
                if (isset($config['format_listener']['rules']) && is_array($config['format_listener']['rules'])) {
                    foreach ($config['format_listener']['rules'] as $key => $rule) {
                        // add backend prefix to API format listener route
                        if (!empty($rule['path']) && $rule['path'] === '^/api/(?!(rest|doc)(/|$)+)') {
                            $backendPrefix = $container->getParameter('web_backend_prefix');
                            $rule['path'] = str_replace('/api/', $backendPrefix . '/api/', $rule['path']);
                            $config['format_listener']['rules'][$key] = $rule;
                            $configs[$configKey] = $config;
                            break 2;
                        }
                    }
                }
            }
            $container->setExtensionConfig('fos_rest', $configs);
        }
    }

    /**
     * @deprecated Since 2.0, will be removed after 2.3
     *
     * Add phone to address format configuration to all locales
     *
     * @param ContainerBuilder $container
     */
    protected function addPhoneToAddress(ContainerBuilder $container)
    {
        $formatAddressLocales = $container->getParameter(OroLocaleExtension::PARAMETER_ADDRESS_FORMATS);

        foreach ($formatAddressLocales as &$locale) {
            $searchResult = stripos($locale['format'], '%%phone%%');
            if (false === $searchResult) {
                $locale['format'] .= "\n%%phone%%";
            }
        }

        $container->setParameter(
            OroLocaleExtension::PARAMETER_ADDRESS_FORMATS,
            $formatAddressLocales
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
