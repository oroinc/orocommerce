<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCheckoutBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addCheckoutTypeColumn($schema);
        $this->setTypeExistingCheckouts($queries);
        $this->createOroB2BDefaultCheckoutTable($schema);
        $this->addOroB2BDefaultCheckoutForeignKeys($schema);
        $this->copyExistingCheckoutsData($queries);
    }

    /**
     * Add checkout type column
     *
     * @param Schema $schema
     */
    protected function addCheckoutTypeColumn(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');
        $table->addColumn('checkout_discriminator', 'string', ['notnull' => false, 'length' => 30]);
    }

    /**
     * Set type existing checkouts
     *
     * @param QueryBag $queries
     */
    protected function setTypeExistingCheckouts(QueryBag $queries)
    {
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class_name',
                [
                    'class_name'  => 'OroB2B\Bundle\CheckoutBundle\Entity\Checkout',
                ],
                [
                    'class_name'  => Type::STRING
                ]
            )
        );
        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_checkout SET checkout_discriminator= :checkout_discriminator',
                [
                    'checkout_discriminator'  => 'checkout',
                ],
                [
                    'checkout_discriminator'  => Type::STRING
                ]
            )
        );
    }

    /**
     * Create orob2b_default_checkout table
     *
     * @param Schema $schema
     */
    protected function createOroB2BDefaultCheckoutTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_default_checkout');

        $table->addColumn('id', 'integer', ['notnull' => true]);
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('save_billing_address', 'boolean', []);
        $table->addColumn('ship_to_billing_address', 'boolean', []);
        $table->addColumn('save_shipping_address', 'boolean', []);

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['billing_address_id'], 'uniq_def_checkout_bill_addr');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_def_checkout_shipp_addr');
        $table->addUniqueIndex(['order_id'], 'uniq_def_checkout_order');
    }

    /**
     * Add orob2b_default_checkout foreign keys
     *
     * @param Schema $schema
     */
    protected function addOroB2BDefaultCheckoutForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_default_checkout');

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_checkout'),
            ['id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['billing_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['shipping_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * @param QueryBag $queries
     */
    protected function copyExistingCheckoutsData(QueryBag $queries)
    {
        $sql = <<<SQL
    INSERT INTO orob2b_default_checkout (id,
        order_id,
        billing_address_id,
        shipping_address_id,
        save_billing_address,
        ship_to_billing_address,
        save_shipping_address)
    SELECT id,
        order_id,
        billing_address_id,
        shipping_address_id,
        save_billing_address,
        ship_to_billing_address,
        save_shipping_address
     FROM orob2b_checkout
SQL;
        $queries->addQuery($sql);
    }
}
