<?php

namespace Oro\Bundle\FrontendTestFrameworkBundle\DependencyInjection\Compiler;

use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass that registers the frontend test client in the DI container.
 *
 * Replaces the default test client with the frontend-specific client implementation
 * during container compilation.
 */
class ClientCompilerPass implements CompilerPassInterface
{
    public const CLIENT_SERVICE = 'test.client';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::CLIENT_SERVICE)) {
            $definition = $container->getDefinition(self::CLIENT_SERVICE);
            $definition->setClass(Client::class);
        }
    }
}
