<?php

namespace OroB2B\Bundle\FallbackBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use OroB2B\Bundle\FrontendBundle\DependencyInjection\Loader\PrivateYamlFileLoader;

class OroB2BFallbackExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PrivateYamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('importexport.yml');
        $loader->load('services.yml');
    }
}
