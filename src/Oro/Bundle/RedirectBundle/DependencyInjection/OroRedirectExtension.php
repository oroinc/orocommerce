<?php

namespace Oro\Bundle\RedirectBundle\DependencyInjection;

use Oro\Bundle\RedirectBundle\Routing\NotInstalledMatchedUrlDecisionMaker;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroRedirectExtension extends Extension
{
    const ALIAS = 'oro_redirect';

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

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
        $this->addClassesToCompile(
            [
                'Oro\Bundle\RedirectBundle\Security\Firewall',
                'Oro\Bundle\RedirectBundle\Routing\SlugUrlMatcher',
                'Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator',
                'Oro\Bundle\RedirectBundle\Routing\Router'
            ]
        );

        $this->configureMatchedUrlDecisionMaker($container);
    }

    /**
     * {@inheritDoc}
     */
    public function getAlias()
    {
        return self::ALIAS;
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureMatchedUrlDecisionMaker(ContainerBuilder $container)
    {
        if ($container->hasParameter('installed') && $container->getParameter('installed')) {
            return;
        }

        $container->getDefinition('oro_redirect.routing.matched_url_decision_maker')
            ->setClass(NotInstalledMatchedUrlDecisionMaker::class);
    }
}
