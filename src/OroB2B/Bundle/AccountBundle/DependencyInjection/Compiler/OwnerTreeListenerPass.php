<?php

namespace OroB2B\Bundle\AccountBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OwnerTreeListenerPass implements CompilerPassInterface
{
    const LISTENER_SERVICE = 'oro_security.ownership_tree_subscriber';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::LISTENER_SERVICE)) {
            return;
        }

        $listenerDefinition = $container->getDefinition(self::LISTENER_SERVICE);
        $listenerDefinition->addMethodCall(
            'addSupportedClass',
            [
                $container->getParameter('orob2b_account.entity.account.class'),
                ['parent', 'organization']
            ]
        );
        $listenerDefinition->addMethodCall(
            'addSupportedClass',
            [
                $container->getParameter('orob2b_account.entity.account_user.class'),
                ['account', 'organization'],
                ['organizations']
            ]
        );
    }
}
