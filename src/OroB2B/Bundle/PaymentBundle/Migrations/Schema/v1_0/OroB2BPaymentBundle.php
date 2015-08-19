<?php

namespace OroB2B\Bundle\PaymentBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroB2BPaymentBundle implements Migration, NoteExtensionAwareInterface
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

        $this->addOroB2BPaymentTermToAccountGroupForeignKeys($schema);
        $this->addOroB2BPaymentTermToAccountForeignKeys($schema);
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
}
