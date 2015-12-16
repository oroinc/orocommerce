<?php

namespace OroB2B\Bundle\AccountBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WindowsStateManagerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_windows.manager.windows_state_registry')) {
            return;
        }

        $container->getDefinition('oro_windows.manager.windows_state_registry')
            ->addMethodCall(
                'addManager',
                [new Reference('orob2b_account.manager.windows_state')]
            );
    }
}
