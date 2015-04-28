<?php

namespace Oro\Bundle\ApplicationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RouterPrefixPass implements CompilerPassInterface
{
    const ROUTER_PREFIX = 'router.cache_class_prefix';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Create new cache_class_prefix considering the new multiapp architecture
        $routerCacheClassPrefix = $container->getParameter('kernel.name')
                                . ucfirst($container->getParameter('kernel.environment'))
                                . ucfirst($container->getParameter('kernel.application'));

        // Redefine cache_class_prefix from SymfonyFrameworkBundle
        $container->setParameter(self::ROUTER_PREFIX, $routerCacheClassPrefix);
    }
}
