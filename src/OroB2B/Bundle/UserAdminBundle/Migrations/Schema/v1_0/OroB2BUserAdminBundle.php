<?php

namespace OroB2B\Bundle\UserAdminBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BUserAdminBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BGroupTable($schema);
        $this->createOroB2BGroupTranslationTable($schema);
        $this->createOroB2BUserTable($schema);
        $this->createOroB2BUserGroupTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BGroupTranslationForeignKeys($schema);
        $this->addOroB2BUserGroupForeignKeys($schema);
    }

    /**
     * Create orob2b_group table
     *
     * @param Schema $schema
     */
    protected function createOroB2BGroupTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_group');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('roles', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['name'], 'UNIQ_58DF28CD5E237E06');
    }

    /**
     * Create orob2b_group_translation table
     *
     * @param Schema $schema
     */
    protected function createOroB2BGroupTranslationTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_group_translation');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('object_id', 'integer', ['notnull' => false]);
        $table->addColumn('locale', 'string', ['length' => 8]);
        $table->addColumn('field', 'string', ['length' => 32]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['object_id'], 'IDX_7F107757232D562B', []);
        $table->addIndex(['locale', 'object_id', 'field'], 'orob2b_group_trans_idx', []);
    }

    /**
     * Create orob2b_user table
     *
     * @param Schema $schema
     */
    protected function createOroB2BUserTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_user');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('username', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('usernameCanonical', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('email_canonical', 'string', ['length' => 255]);
        $table->addColumn('enabled', 'boolean', []);
        $table->addColumn('salt', 'string', ['length' => 255]);
        $table->addColumn('password', 'string', ['length' => 255]);
        $table->addColumn('last_login', 'datetime', ['notnull' => false]);
        $table->addColumn('locked', 'boolean', []);
        $table->addColumn('expired', 'boolean', []);
        $table->addColumn('expires_at', 'datetime', ['notnull' => false]);
        $table->addColumn('confirmation_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('password_requested_at', 'datetime', ['notnull' => false]);
        $table->addColumn('roles', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('credentials_expired', 'boolean', []);
        $table->addColumn('credentials_expire_at', 'datetime', ['notnull' => false]);
        $table->addColumn('first_name', 'string', ['length' => 255]);
        $table->addColumn('last_name', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email_canonical'], 'UNIQ_27572461A0D96FBF');
    }

    /**
     * Create orob2b_user_group table
     *
     * @param Schema $schema
     */
    protected function createOroB2BUserGroupTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_user_group');
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('group_id', 'integer', []);
        $table->setPrimaryKey(['user_id', 'group_id']);
        $table->addIndex(['user_id'], 'IDX_7BC99A1CA76ED395', []);
        $table->addIndex(['group_id'], 'IDX_7BC99A1CFE54D947', []);
    }

    /**
     * Add orob2b_group_translation foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BGroupTranslationForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_group_translation');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_group'),
            ['object_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_user_group foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BUserGroupForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_user_group');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_group'),
            ['group_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_user'),
            ['user_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
