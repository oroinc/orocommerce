<?php

namespace OroB2B\Bundle\AttributeBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAttributeBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BAttributeTable($schema);
        $this->createOrob2BAttributeDefaultValueTable($schema);
        $this->createOrob2BAttributeLabelTable($schema);
        $this->createOrob2BAttributeOptionTable($schema);
        $this->createOrob2BAttributePropertyTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BAttributeDefaultValueForeignKeys($schema);
        $this->addOrob2BAttributeLabelForeignKeys($schema);
        $this->addOrob2BAttributeOptionForeignKeys($schema);
        $this->addOrob2BAttributePropertyForeignKeys($schema);
    }

    /**
     * Create orob2b_attribute table
     *
     * @param Schema $schema
     */
    protected function createOrob2BAttributeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_attribute');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('type', 'string', ['length' => 64]);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('sharing_type', 'string', ['length' => 64]);
        $table->addColumn('validation', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('contain_html', 'boolean', []);
        $table->addColumn('localized', 'boolean', []);
        $table->addColumn('system', 'boolean', []);
        $table->addColumn('required', 'boolean', []);
        $table->addColumn('unique_flag', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_E5EA3FED77153098');
    }

    /**
     * Create orob2b_attribute_default_value table
     *
     * @param Schema $schema
     */
    protected function createOrob2BAttributeDefaultValueTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_attribute_default_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('option_id', 'integer', ['notnull' => false]);
        $table->addColumn('attribute_id', 'integer', ['notnull' => false]);
        $table->addColumn('locale_id', 'integer', ['notnull' => false]);
        $table->addColumn('integer_value', 'integer', ['notnull' => false]);
        $table->addColumn('string_value', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('float_value', 'float', ['notnull' => false]);
        $table->addColumn('datetime_value', 'datetime', ['notnull' => false]);
        $table->addColumn('text_value', 'text', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['attribute_id'], 'IDX_488AC0BBB6E62EFA', []);
        $table->addIndex(['locale_id'], 'IDX_488AC0BBE559DFD1', []);
        $table->addIndex(['option_id'], 'IDX_488AC0BBA7C41D6F', []);
    }

    /**
     * Create orob2b_attribute_label table
     *
     * @param Schema $schema
     */
    protected function createOrob2BAttributeLabelTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_attribute_label');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('attribute_id', 'integer', ['notnull' => false]);
        $table->addColumn('locale_id', 'integer', ['notnull' => false]);
        $table->addColumn('value', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['attribute_id'], 'IDX_587CF949B6E62EFA', []);
        $table->addIndex(['locale_id'], 'IDX_587CF949E559DFD1', []);
        $table->addUniqueIndex(['attribute_id', 'locale_id'], 'attribute_label_unique_idx');
    }

    /**
     * Create orob2b_attribute_option table
     *
     * @param Schema $schema
     */
    protected function createOrob2BAttributeOptionTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_attribute_option');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('attribute_id', 'integer', ['notnull' => false]);
        $table->addColumn('locale_id', 'integer', ['notnull' => false]);
        $table->addColumn('value', 'string', ['length' => 255]);
        $table->addColumn('order_value', 'integer', []);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['attribute_id'], 'IDX_B5688EBAB6E62EFA', []);
        $table->addIndex(['locale_id'], 'IDX_B5688EBAE559DFD1', []);
        $table->addUniqueIndex(['attribute_id', 'locale_id'], 'attribute_option_unique_idx');
    }

    /**
     * Create orob2b_attribute_property table
     *
     * @param Schema $schema
     */
    protected function createOrob2BAttributePropertyTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_attribute_property');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('attribute_id', 'integer', ['notnull' => false]);
        $table->addColumn('field', 'string', ['length' => 64]);
        $table->addColumn('value', 'boolean', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['attribute_id'], 'IDX_D3FF0DBBB6E62EFA', []);
        $table->addIndex(['website_id'], 'IDX_D3FF0DBB18F45C82', []);
        $table->addUniqueIndex(['attribute_id', 'website_id', 'field'], 'attribute_property_unique_idx');
    }

    /**
     * Add orob2b_attribute_default_value foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BAttributeDefaultValueForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_attribute_default_value');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_attribute_option'),
            ['option_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_attribute'),
            ['attribute_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_locale'),
            ['locale_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_attribute_label foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BAttributeLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_attribute_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_attribute'),
            ['attribute_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_locale'),
            ['locale_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_attribute_option foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BAttributeOptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_attribute_option');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_attribute'),
            ['attribute_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_locale'),
            ['locale_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_attribute_property foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BAttributePropertyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_attribute_property');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_attribute'),
            ['attribute_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
