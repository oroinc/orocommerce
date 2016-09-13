<?php

namespace Oro\Bundle\AccountBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WindowsStateManagerPass implements CompilerPassInterface
{
    const WINDOWS_STATE_REGISTRY = 'oro_windows.manager.windows_state_registry';
    const COMMERCE_WINDOWS_STATE = 'oro_account.manager.windows_state';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::WINDOWS_STATE_REGISTRY)) {
            return;
        }

        $container->getDefinition(self::WINDOWS_STATE_REGISTRY)
            ->addMethodCall(
                'addManager',
                [new Reference(self::COMMERCE_WINDOWS_STATE)]
            );
    }
}
