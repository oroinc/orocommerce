<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroPricingBundle implements Migration, OrderedMigrationInterface, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroPriceListScheduleTable($schema);
        $this->createOroCplActivationRuleTable($schema);
        $this->createOrob2BPriceProductMinimalTable($schema);

        $this->addOrob2BPriceListScheduleForeignKeys($schema);
        $this->addOrob2BCplActivationRuleForeignKeys($schema);
        $this->addOrob2BPriceProductMinimalForeignKeys($schema, $queries);

        $this->createOroCmbPriceListToAccTable($schema);
        $this->createOroCmbPriceListToAccGrTable($schema);
        $this->createOroCmbPriceListToWsTable($schema);

        $this->addOrob2BCmbPriceListToAccGrForeignKeys($schema, $queries);
        $this->addOrob2BCmbPriceListToWsForeignKeys($schema, $queries);
        $this->addOrob2BCmbPriceListToAccForeignKeys($schema, $queries);

        $this->alterOroPriceListTable($schema, $queries);
        $this->alterOroPriceListCombinedTable($schema, $queries);

        $this->updatePriceListChangeTriggerTable($schema);

        $queries->addPostQuery(new UpdateCPLRelationsQuery('orob2b_cmb_price_list_to_acc'));
        $queries->addPostQuery(new UpdateCPLRelationsQuery('orob2b_cmb_plist_to_acc_gr'));
        $queries->addPostQuery(new UpdateCPLRelationsQuery('orob2b_cmb_price_list_to_ws'));
        $queries->addPostQuery(new UpdateCPLNameQuery());
        $queries->addPostQuery(new AddJobQuery('oro:cron:price-lists:recalculate', ['--force']));
        $queries->addPostQuery(new FillMinimalPrices());
    }

    /**
     * Create orob2b_price_list_schedule table
     *
     * @param Schema $schema
     */
    protected function createOroPriceListScheduleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_list_schedule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('active_at', 'datetime', ['notnull' => false]);
        $table->addColumn('deactivate_at', 'datetime', ['notnull' => false]);
        $table->addIndex(['price_list_id'], 'IDX_C706756E5688DED7', []);
    }

    /**
     * Create orob2b_cpl_activation_rule table
     *
     * @param Schema $schema
     */
    protected function createOroCplActivationRuleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cpl_activation_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('activate_at', 'datetime', ['notnull' => false]);
        $table->addColumn('expire_at', 'datetime', ['notnull' => false]);
        $table->addColumn('is_active', 'boolean', []);
    }

    /**
     * Create orob2b_cpl_activation_rule table
     *
     * @param Schema $schema
     */
    protected function updatePriceListChangeTriggerTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_ch_trigger');
        $table->addColumn('is_force', 'boolean', ['notnull' => false]);
    }

    /**
     * Add orob2b_price_list_schedule foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BPriceListScheduleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_price_list_schedule');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list'),
            ['price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_cpl_activation_rule foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCplActivationRuleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_cpl_activation_rule');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_cmb_price_list_to_acc table
     *
     * @param Schema $schema
     */
    protected function createOroCmbPriceListToAccTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cmb_price_list_to_acc');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('account_id', 'integer', ['notnull' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['account_id', 'website_id'], 'orob2b_cpl_to_acc_ws_unq');
    }

    /**
     * Create orob2b_cmb_plist_to_acc_gr table
     *
     * @param Schema $schema
     */
    protected function createOroCmbPriceListToAccGrTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cmb_plist_to_acc_gr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('account_group_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['account_group_id', 'website_id'], 'orob2b_cpl_to_acc_gr_ws_unq');
    }

    /**
     * Create orob2b_cmb_price_list_to_ws table
     *
     * @param Schema $schema
     */
    protected function createOroCmbPriceListToWsTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_cmb_price_list_to_ws');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('full_combined_price_list_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['website_id'], 'orob2b_cpl_to_ws_unq');
    }

    /**
     * Add orob2b_cmb_plist_to_acc_gr foreign keys.
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addOrob2BCmbPriceListToAccGrForeignKeys(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_cmb_plist_to_acc_gr');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_cmb_plist_to_acc_gr',
            'oro_website',
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_cmb_plist_to_acc_gr',
            'oro_account_group',
            ['account_group_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_cmb_price_list_to_ws foreign keys.
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addOrob2BCmbPriceListToWsForeignKeys(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_cmb_price_list_to_ws');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_cmb_price_list_to_ws',
            'oro_website',
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_cmb_price_list_to_acc foreign keys.
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addOrob2BCmbPriceListToAccForeignKeys(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_cmb_price_list_to_acc');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['full_combined_price_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_cmb_price_list_to_acc',
            'oro_website',
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_cmb_price_list_to_acc',
            'oro_account',
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterOroPriceListTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_price_list');
        $table->addColumn('contain_schedule', 'boolean', ['notnull' => false]);

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_price_list SET contain_schedule = :contain_schedule',
                [
                    'contain_schedule' => false,
                ],
                [
                    'contain_schedule' => Type::BOOLEAN
                ]
            )
        );
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function alterOroPriceListCombinedTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_price_list_combined');
        $table->addColumn('is_prices_calculated', 'boolean', ['notnull' => false]);

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_price_list_combined as cpl SET is_prices_calculated = 
                (SELECT p.id IS NOT NULL FROM orob2b_price_product_combined as p 
                WHERE p.combined_price_list_id = cpl.id LIMIT 1)'
            )
        );
    }

    /**
     * Create orob2b_price_product_minimal table
     *
     * @param Schema $schema
     */
    protected function createOrob2BPriceProductMinimalTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_price_product_minimal');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('combined_price_list_id', 'integer', []);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float', []);
        $table->addColumn('value', 'money', ['precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']);
        $table->addColumn('currency', 'string', ['length' => 3]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            [
                'product_id',
                'combined_price_list_id',
                'currency',
            ],
            'orob2b_minimal_price_uidx'
        );
    }

    /**
     * Add orob2b_price_product_minimal foreign keys.
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function addOrob2BPriceProductMinimalForeignKeys(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_price_product_minimal');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_price_product_minimal',
            'oro_product_unit',
            ['unit_code'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'orob2b_price_product_minimal',
            'oro_product',
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
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
