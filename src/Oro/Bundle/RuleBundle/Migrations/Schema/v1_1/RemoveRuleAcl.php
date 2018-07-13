<?php

namespace Oro\Bundle\RuleBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\MassUpdateEntityConfigQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\SecurityBundle\Migration\DeleteAclMigrationQuery;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

class RemoveRuleAcl implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery(
            new DeleteAclMigrationQuery(
                $this->container,
                new ObjectIdentity('entity', Rule::class)
            )
        );

        $queries->addPostQuery(
            new MassUpdateEntityConfigQuery(Rule::class, ['security' => ['type', 'group_name']], [])
        );
    }
}
