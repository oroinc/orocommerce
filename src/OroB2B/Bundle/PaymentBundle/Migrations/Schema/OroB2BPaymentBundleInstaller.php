<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroB2BPaymentBundleInstaller implements Installation, NoteExtensionAwareInterface
{
    /**
     * Table name for PaymentTerm
     */
    const TABLE_NAME                          = 'orob2b_payment_term';
    const PAYMENT_TERM_TO_ACCOUNT_TABLE       = 'orob2b_payment_term_to_account';
    const PAYMENT_TERM_TO_ACCOUNT_GROUP_TABLE = 'orob2b_payment_term_to_acc_grp';
    const ACCOUNT_TABLE                       = 'orob2b_account';
    const ACCOUNT_GROUP_TABLE                 = 'orob2b_account_group';

    /**
     * @var NoteExtension
     */
    protected $noteExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_3';
    }

    /**
     * @param NoteExtension $noteExtension
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BPaymentTermTable($schema);
        $this->addNoteAssociations($schema);
        $this->createOroB2BPaymentIntersectionTables($schema);
        $this->createOroB2BPaymentTransactionTable($schema);

        $this->addOroB2BPaymentTermToAccountGroupForeignKeys($schema);
        $this->addOroB2BPaymentTermToAccountForeignKeys($schema);
        $this->addOroB2BPaymentTransactionForeignKeys($schema);
    }

    /**
     * Create table for PaymentTerm entity
     *
     * @param Schema $schema
     */
    protected function createOroB2BPaymentTermTable(Schema $schema)
    {
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('label', 'string');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Enable notes for PaymentTerm entity
     *
     * @param Schema $schema
     */
    protected function addNoteAssociations(Schema $schema)
    {
        $this->noteExtension->addNoteAssociation($schema, self::TABLE_NAME);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BPaymentIntersectionTables(Schema $schema)
    {
        $table = $schema->createTable(static::PAYMENT_TERM_TO_ACCOUNT_TABLE);
        $table->addColumn('payment_term_id', 'integer', []);
        $table->addColumn('account_id', 'integer', []);
        $table->setPrimaryKey(['payment_term_id', 'account_id']);
        $table->addUniqueIndex(['account_id']);

        $table = $schema->createTable(static::PAYMENT_TERM_TO_ACCOUNT_GROUP_TABLE);
        $table->addColumn('payment_term_id', 'integer', []);
        $table->addColumn('account_group_id', 'integer', []);
        $table->setPrimaryKey(['payment_term_id', 'account_group_id']);
        $table->addUniqueIndex(['account_group_id']);
    }

    /**
     * Create table for PaymentTransaction entity
     *
     * @param Schema $schema
     */
    protected function createOroB2BPaymentTransactionTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_payment_transaction');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('entity_identifier', 'integer', []);
        $table->addColumn('access_identifier', 'string', ['length' => 255]);
        $table->addColumn('access_token', 'string', ['length' => 255]);
        $table->addColumn('payment_method', 'string', ['length' => 255]);
        $table->addColumn('action', 'string', ['length' => 255]);
        $table->addColumn('reference', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('amount', 'string', ['length' => 255]);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addColumn('active', 'boolean', []);
        $table->addColumn('successful', 'boolean', []);
        $table->addColumn('source_payment_transaction', 'integer', ['notnull' => false]);
        $table->addColumn('request', 'secure_array', ['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->addColumn('response', 'secure_array', ['notnull' => false, 'comment' => '(DC2Type:secure_array)']);
        $table->addColumn('transaction_options', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('frontend_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['access_identifier', 'access_token'], 'access_idx');
        $table->addIndex(['source_payment_transaction']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BPaymentTermToAccountForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::PAYMENT_TERM_TO_ACCOUNT_TABLE);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ACCOUNT_TABLE),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::TABLE_NAME),
            ['payment_term_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BPaymentTermToAccountGroupForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::PAYMENT_TERM_TO_ACCOUNT_GROUP_TABLE);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::ACCOUNT_GROUP_TABLE),
            ['account_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(static::TABLE_NAME),
            ['payment_term_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_payment_transaction foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BPaymentTransactionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_payment_transaction');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_payment_transaction'),
            ['source_payment_transaction'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['frontend_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
