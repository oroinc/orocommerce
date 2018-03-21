<?php

namespace Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This class collect and process all services that have tag
 *
 * @package Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Compiler
 */
class WebsiteSearchTypeProviderCompilerPass implements CompilerPassInterface
{
    protected const SERVICE_TAG = 'oro_website_search.search_type';
    protected const SERVICE_NAME = 'oro_website_search.search_type_chain_provider';

    /**
     * You can modify the container here before it is dumped to PHP code.
     * @throws \LogicException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::SERVICE_NAME)) {
            return;
        }
        $definition      = $container->findDefinition(self::SERVICE_NAME);
        $taggedServices  = $container->findTaggedServiceIds(self::SERVICE_TAG);
        $hasDefaultValue = false;
        $resolvers       = [];

        if (\count($taggedServices) <= 0) {
            throw new \LogicException(
                sprintf(
                    'At least one service must be defined with tag [%s]',
                    self::SERVICE_TAG
                )
            );
        }

        foreach ($taggedServices as $id => $attributes) {
            $order     = $attributes[0]['order'] ?? 0;
            $type      = $attributes[0]['type'] ?? null;
            $isDefault = $attributes[0]['isDefault'] ?? false;

            if ($isDefault === true) {
                $hasDefaultValue = true;
            }

            if (null === $type) {
                throw new \LogicException(
                    sprintf('Parameter `%s` should be defined for service %s', 'type', self::SERVICE_TAG)
                );
            }

            $resolvers[$order][] = [
                'service'   => new Reference($id),
                'type'      => $type,
                'isDefault' => $isDefault,
            ];
        }

        if (false === $hasDefaultValue) {
            throw new \LogicException(
                sprintf(
                    'At least one service must have parameter [%s] as true for tag [%s]',
                    'isDefault',
                    self::SERVICE_TAG
                )
            );
        }

        ksort($resolvers);
        $resolvers = array_merge(...$resolvers);

        foreach ($resolvers as $value) {
            if ($value['isDefault'] === true) {
                $definition->addMethodCall('setDefaultSearchType', [$value['service']]);
            }
            $definition->addMethodCall('addSearchType', [$value['type'], $value['service']]);
        }
    }
}
