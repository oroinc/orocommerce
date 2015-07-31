<?php

namespace OroB2B\Bundle\AccountBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OwnerTreeListenerPass implements CompilerPassInterface
{
    const LISTENER_SERVICE = 'oro_security.ownership_tree_subscriber';

    /**
     * @var array
     */
    protected static $supportedEntities = [
        'orob2b_account.entity.account_user.class',
        'orob2b_account.entity.account.class',
    ];

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::LISTENER_SERVICE)) {
            return;
        }

        $listenerDefinition = $container->getDefinition(self::LISTENER_SERVICE);

        foreach (static::$supportedEntities as $entity) {
            $listenerDefinition->addMethodCall('addSupportedClass', [$container->getParameter($entity)]);
        }
    }
}
