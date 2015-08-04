<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddOrderAddress implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BOrderAddressTable($schema);
        $this->createOroB2BOrderAddressTypeTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BOrderAddressForeignKeys($schema);
        $this->addOroB2BOrderAddressTypeForeignKeys($schema);
    }

    /**
     * Create orob2b_order_address table
     *
     * @param Schema $schema
     */
    protected function createOroB2BOrderAddressTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_order_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_primary', 'boolean', ['notnull' => false]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('street', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('street2', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('city', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('postal_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('organization', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['region_code'], 'idx_ff867c56aeb327af', []);
        $table->addIndex(['country_code'], 'idx_ff867c56f026bb7c', []);
        $table->addIndex(['owner_id'], 'idx_ff867c567e3c61f9', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_order_address_type table
     *
     * @param Schema $schema
     */
    protected function createOroB2BOrderAddressTypeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_order_address_type');
        $table->addColumn('order_address_id', 'integer', []);
        $table->addColumn('type_name', 'string', ['length' => 16]);
        $table->setPrimaryKey(['order_address_id', 'type_name']);
        $table->addIndex(['type_name'], 'idx_31dd983d892cbb0e', []);
        $table->addIndex(['order_address_id'], 'idx_31dd983d466d5220', []);
    }

    /**
     * Add orob2b_order_address foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BOrderAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_order_address_type foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BOrderAddressTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_order_address_type');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_address_type'),
            ['type_name'],
            ['name'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['order_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
