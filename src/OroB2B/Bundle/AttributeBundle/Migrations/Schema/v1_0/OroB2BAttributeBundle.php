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
        $this->createOrob2BAttributeDefaultValuesTable($schema);
        $this->createOrob2BAttributeLabelsTable($schema);
        $this->createOrob2BAttributeOptionsTable($schema);
        $this->createOrob2BAttributePropertyTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BAttributeDefaultValuesForeignKeys($schema);
        $this->addOrob2BAttributeLabelsForeignKeys($schema);
        $this->addOrob2BAttributeOptionsForeignKeys($schema);
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
        $table->addColumn('validation', 'string', ['length' => 255]);
        $table->addColumn('contain_html', 'boolean', []);
        $table->addColumn('localized', 'boolean', []);
        $table->addColumn('system', 'boolean', []);
        $table->addColumn('unique', 'boolean', []);
        $table->addColumn('required', 'boolean', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['code'], 'UNIQ_E5EA3FED77153098');
    }

    /**
     * Create orob2b_attribute_default_value table
     *
     * @param Schema $schema
     */
    protected function createOrob2BAttributeDefaultValuesTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_attribute_default_value');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('option_id', 'integer', ['notnull' => false]);
        $table->addColumn('attribute_id', 'integer', ['notnull' => false]);
        $table->addColumn('locale_id', 'integer', ['notnull' => false]);
        $table->addColumn('integer', 'integer', ['notnull' => false]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('float', 'float', ['notnull' => false]);
        $table->addColumn('datetime', 'datetime', ['notnull' => false]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['length' => 64]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
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
    protected function createOrob2BAttributeLabelsTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_attribute_label');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('locale_id', 'integer', ['notnull' => false]);
        $table->addColumn('attribute_id', 'integer', ['notnull' => false]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('fallback', 'string', ['length' => 64]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['attribute_id'], 'IDX_587CF949B6E62EFA', []);
        $table->addIndex(['locale_id'], 'IDX_587CF949E559DFD1', []);
    }

    /**
     * Create orob2b_attribute_option table
     *
     * @param Schema $schema
     */
    protected function createOrob2BAttributeOptionsTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_attribute_option');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('locale_id', 'integer', ['notnull' => false]);
        $table->addColumn('attribute_id', 'integer', ['notnull' => false]);
        $table->addColumn('value', 'string', ['length' => 255]);
        $table->addColumn('order', 'integer', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['value'], 'UNIQ_B5688EBA1D775834');
        $table->addIndex(['attribute_id'], 'IDX_B5688EBAB6E62EFA', []);
        $table->addIndex(['locale_id'], 'IDX_B5688EBAE559DFD1', []);
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
        $table->addColumn('on_product_view', 'boolean', []);
        $table->addColumn('use_in_sorting', 'boolean', []);
        $table->addColumn('on_advanced_search', 'boolean', []);
        $table->addColumn('on_product_comparison', 'boolean', []);
        $table->addColumn('in_filters', 'boolean', []);
        $table->addColumn('fallback', 'string', ['length' => 64]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['attribute_id'], 'IDX_D3FF0DBBB6E62EFA', []);
        $table->addIndex(['website_id'], 'IDX_D3FF0DBB18F45C82', []);
    }

    /**
     * Add orob2b_attribute_default_value foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BAttributeDefaultValuesForeignKeys(Schema $schema)
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
    protected function addOrob2BAttributeLabelsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_attribute_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_locale'),
            ['locale_id'],
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

    /**
     * Add orob2b_attribute_option foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BAttributeOptionsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_attribute_option');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_locale'),
            ['locale_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_attribute'),
            ['attribute_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
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
