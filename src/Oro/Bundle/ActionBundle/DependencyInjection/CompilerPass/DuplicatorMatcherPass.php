<?php

namespace OroB2B\src\Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DuplicatorMatcherPass implements CompilerPassInterface
{
    const TAG_NAME = 'oro_action.duplicate.matcher_type';
    const CONDITION_SERVICE_ID = 'oro_action.factory.duplicator_matcher_factory';

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $matchers = $container->findTaggedServiceIds(self::TAG_NAME);

        $service = $container->getDefinition(self::CONDITION_SERVICE_ID);

        foreach ($matchers as $matcher => $tags) {
            $service->addMethodCall('addObjectType', [new Reference($matcher)]);
        }
    }
}
