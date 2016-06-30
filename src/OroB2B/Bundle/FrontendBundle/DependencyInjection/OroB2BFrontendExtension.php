<?php

namespace OroB2B\Bundle\FrontendBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;

use Oro\Bundle\LocaleBundle\DependencyInjection\OroLocaleExtension;

class OroB2BFrontendExtension extends Extension
{
    const ALIAS = 'oro_b2b_frontend';
    
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

        $this->addPhoneToAddress($container);

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }

    /**
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
}
