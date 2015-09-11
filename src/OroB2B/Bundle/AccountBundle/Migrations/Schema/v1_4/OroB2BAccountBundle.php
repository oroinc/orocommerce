<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAccountBundle implements Migration
{
    const ORO_B2B_ACCOUNT_TABLE_NAME = 'orob2b_account';
    const ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME = 'orob2b_account_user_role';
    const ORO_ORGANIZATION_TABLE_NAME = 'oro_organization';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BAccountUserRoleTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BAccountUserRoleForeignKeys($schema);
    }

    /**
     * Create orob2b_account_user_role table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAccountUserRoleTable(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['account_id', 'label'], 'orob2b_account_user_role_account_id_label_idx');
    }

    /**
     * Add orob2b_account_user_role foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAccountUserRoleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::ORO_B2B_ACCOUNT_USER_ROLE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_ORGANIZATION_TABLE_NAME),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ORO_B2B_ACCOUNT_TABLE_NAME),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
