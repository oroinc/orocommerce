<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroShoppingListBundleInstaller implements Installation, ExtendExtensionAwareInterface
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
    public function getMigrationVersion()
    {
        return 'v1_4';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroShoppingListTable($schema);
        $this->createOroShoppingListLineItemTable($schema);
        $this->createOroShoppingListTotalTable($schema);

        /** Foreign keys generation **/
        $this->addOroShoppingListForeignKeys($schema);
        $this->addOroShoppingListLineItemForeignKeys($schema);
        $this->addOroShoppingListTotalForeignKeys($schema);

        $this->addShoppingListCheckoutSource($schema);
    }

    /**
     * Create oro_shopping_list_total table
     *
     * @param Schema $schema
     */
    protected function createOroShoppingListTotalTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shopping_list_total');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('shopping_list_id', 'integer');
        $table->addColumn('currency', 'string', ['length' => 255]);
        $table->addColumn(
            'subtotal_value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('is_valid', 'boolean');
        $table->addUniqueIndex(['shopping_list_id', 'currency'], 'unique_shopping_list_currency');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_shopping_list table
     *
     * @param Schema $schema
     */
    protected function createOroShoppingListTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shopping_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('label', 'string', ['length' => 255]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('is_current', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'oro_shop_lst_created_at_idx', []);
    }

    /**
     * Create oro_shopping_list_line_item table
     *
     * @param Schema $schema
     */
    protected function createOroShoppingListLineItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_shopping_list_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('shopping_list_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('unit_code', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float');
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(
            ['product_id', 'shopping_list_id', 'unit_code'],
            'oro_shopping_list_line_item_uidx'
        );
    }

    /**
     * Add oro_shopping_list_total foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShoppingListTotalForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shopping_list_total');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shopping_list'),
            ['shopping_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_shopping_list foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShoppingListForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shopping_list');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_shopping_list_line_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShoppingListLineItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shopping_list_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shopping_list'),
            ['shopping_list_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addShoppingListCheckoutSource(Schema $schema)
    {
        if (class_exists('Oro\Bundle\CheckoutBundle\Entity\CheckoutSource')) {
            $this->extendExtension->addManyToOneRelation(
                $schema,
                'oro_checkout_source',
                'shoppingList',
                'oro_shopping_list',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.shoppinglist.entity_label'],
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
}
