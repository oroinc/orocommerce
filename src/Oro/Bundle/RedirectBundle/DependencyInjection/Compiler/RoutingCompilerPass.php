<?php

namespace Oro\Bundle\RedirectBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RoutingCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $routerListenerDefinition = $container->getDefinition('router_listener');
        /** @var Reference $matcher */
        $matcher = $routerListenerDefinition->getArgument(0);
        $slugMatcherDefinition = $container->getDefinition('oro_redirect.routing.slug_url_mathcer');
        $slugMatcherDefinition->replaceArgument(0, $matcher);

        $routerListenerDefinition->replaceArgument(0, $slugMatcherDefinition);
    }
}
