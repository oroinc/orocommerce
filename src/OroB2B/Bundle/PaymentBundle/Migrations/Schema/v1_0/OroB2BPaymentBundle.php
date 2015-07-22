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
    const TABLE_NAME                           = 'orob2b_payment_term';
    const PAYMENT_TERM_TO_CUSTOMER_TABLE       = 'orob2b_payment_t_to_customer';
    const PAYMENT_TERM_TO_CUSTOMER_GROUP_TABLE = 'orob2b_payment_term_to_c_group';
    const CUSTOMER_TABLE                       = 'orob2b_customer';
    const CUSTOMER_GROUP_TABLE                 = 'orob2b_customer_group';

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

        $this->addOroB2BPaymentTermToCustomerGroupForeignKeys($schema);
        $this->addOroB2BPaymentTermToCustomerForeignKeys($schema);
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
        $table = $schema->createTable(static::PAYMENT_TERM_TO_CUSTOMER_TABLE);
        $table->addColumn('payment_term_id', 'integer', []);
        $table->addColumn('customer_id', 'integer', []);
        $table->setPrimaryKey(['payment_term_id', 'customer_id']);
        $table->addUniqueIndex(['customer_id'], 'UNIQ_BF9D98859395C3F3');
        $table->addIndex(['payment_term_id'], 'IDX_BF9D988517653B16');

        $table = $schema->createTable(static::PAYMENT_TERM_TO_CUSTOMER_GROUP_TABLE);
        $table->addColumn('payment_term_id', 'integer', []);
        $table->addColumn('customer_group_id', 'integer', []);
        $table->setPrimaryKey(['payment_term_id', 'customer_group_id']);
        $table->addUniqueIndex(['customer_group_id'], 'UNIQ_A94D3ED6D2919A68');
        $table->addIndex(['payment_term_id'], 'IDX_A94D3ED617653B16');
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BPaymentTermToCustomerForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::PAYMENT_TERM_TO_CUSTOMER_TABLE);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::CUSTOMER_TABLE),
            ['customer_id'],
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
    protected function addOroB2BPaymentTermToCustomerGroupForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(static::PAYMENT_TERM_TO_CUSTOMER_GROUP_TABLE);
        $table->addForeignKeyConstraint(
            $schema->getTable(static::CUSTOMER_GROUP_TABLE),
            ['customer_group_id'],
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
