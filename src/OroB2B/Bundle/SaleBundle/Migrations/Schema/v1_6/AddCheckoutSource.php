<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCheckoutSource implements Migration, ExtendExtensionAwareInterface
{
    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroSaleQuoteDemandTable($schema);
        $this->createOroSaleQuoteProductDemandTable($schema);
        $this->addOroSaleQuoteProductDemandForeignKeys($schema);

        if (class_exists('Oro\Bundle\CheckoutBundle\Entity\CheckoutSource')) {
            $this->extendExtension->addManyToOneRelation(
                $schema,
                'orob2b_checkout_source',
                'quoteDemand',
                'orob2b_quote_demand',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.sale.quote.entity_label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM
                    ],
                    'datagrid' => [
                        'is_visible' => false
                    ],
                    'form' => [
                        'is_enabled' => false
                    ],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => false]
                ]
            );
        }
    }

    /**
     * Create orob2b_quote_demand table
     *
     * @param Schema $schema
     */
    protected function createOroSaleQuoteDemandTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_quote_demand');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quote_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_quote_product_demand table
     *
     * @param Schema $schema
     */
    protected function createOroSaleQuoteProductDemandTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_quote_product_demand');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('quote_demand_id', 'integer', ['notnull' => false]);
        $table->addColumn('quote_product_offer_id', 'integer', ['notnull' => false]);
        $table->addColumn('quantity', 'float', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_quote_product_demand foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSaleQuoteProductDemandForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_quote_product_demand');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_quote_demand'),
            ['quote_demand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_sale_quote_prod_offer'),
            ['quote_product_offer_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
