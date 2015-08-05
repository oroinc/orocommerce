<?php

namespace OroB2B\Bundle\AccountBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DataAuditEntityMappingPass implements CompilerPassInterface
{
    const MAPPER_SERVICE = 'oro_dataaudit.loggable.audit_entity_mapper';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::MAPPER_SERVICE)) {
            return;
        }

        $mapperDefinition = $container->getDefinition(self::MAPPER_SERVICE);
        $accountUserClass = $container->getParameter('orob2b_account.entity.account_user.class');

        $mapperDefinition->addMethodCall(
            'addAuditEntryClass',
            [
                $accountUserClass,
                $container->getParameter('orob2b_account.entity.audit.class'),
            ]
        );

        $mapperDefinition->addMethodCall(
            'addAuditEntryFieldClass',
            [
                $accountUserClass,
                $container->getParameter('orob2b_account.entity.audit_field.class'),
            ]
        );
    }
}
