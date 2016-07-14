<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\AccountBundle\Migrations\Schema\LoadRolesDataTrait;

class AddSelfManagedDataColumn implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_account_user_role');
        $table->addColumn('self_managed', 'boolean', ['notnull' => false]);

        $this->updateAccountUserRoles($queries);
    }

    /**
     * @param QueryBag $queries
     */
    public function updateAccountUserRoles(QueryBag $queries)
    {
        $anonymousRoleName = 'IS_AUTHENTICATED_ANONYMOUSLY';

        $queries->addPostQuery(
            "update orob2b_account_user_role set self_managed = true where role <> '$anonymousRoleName'"
        );
    }
}
