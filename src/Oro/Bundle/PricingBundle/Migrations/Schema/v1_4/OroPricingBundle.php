<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingBundle implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroPriceAttributeTable($schema);
        $this->createOroPriceAttributeCurrencyTable($schema);
        $this->createOroPriceAttributeProductPriceTable($schema);
        $this->createOroriceListToProductTable($schema);

        /** Foreign keys generation **/
        $this->addOroPriceAttributeCurrencyForeignKeys($schema);
        $this->addOroPriceAttributeProductPriceForeignKeys($schema, $queries);
        $this->addOroriceListToProductForeignKeys($schema, $queries);

        $queries->addPostQuery(new FillPriceListToProduct());
    }

    /**
     * @param Schema $schema
     */
    protected function createOroPriceAttributeTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_attribute_pl');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroPriceAttributeCurrencyTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_product_attr_currency');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_attribute_pl_id', 'integer', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroPriceAttributeProductPriceTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_attribute_price');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('price_attribute_pl_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('value', 'money', []);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->addUniqueIndex(
            ['product_id', 'price_attribute_pl_id', 'quantity', 'unit_code', 'currency'],
            'orob2b_pricing_price_attribute_uidx'
        );
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroPriceAttributeCurrencyForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_product_attr_currency');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_attribute_pl'),
            ['price_attribute_pl_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addOroPriceAttributeProductPriceForeignKeys(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_price_attribute_price');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_attribute_pl'),
            ['price_attribute_pl_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_price_attribute_price',
            'oro_product',
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_price_attribute_price',
            'oro_product_unit',
            ['unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Create orob2b_price_list_to_product table
     *
     * @param Schema $schema
     */
    protected function createOroriceListToProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_to_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('price_list_id', 'integer', []);
        $table->addColumn('is_manual', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'price_list_id'], 'orob2b_price_list_to_product_uidx');
    }
    
    /**
     * Add orob2b_price_list_to_product foreign keys.
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addOroriceListToProductForeignKeys(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_price_list_to_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_price_list_to_product',
            'oro_product',
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
