<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds "order" column to "oro_checkout" table and migrate it's values from associated WorkflowItem if any.
 */
class AddOrderToCheckout implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_checkout');
        if ($table->hasColumn('order_id')) {
            return;
        }
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['order_id'], 'UNIQ_C040FD598D9F6D38');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_checkout SET order_id = '
                . '(CASE WHEN (wi.data IS NULL OR wi.data NOT LIKE \'{%\') THEN NULL'
                . ' ELSE ('
                . 'CASE WHEN wi.data::jsonb->>\'order\' NOT LIKE \'{%\' THEN NULL '
                . 'WHEN NOT EXISTS ('
                . 'SELECT 1 FROM oro_order WHERE id = ((wi.data::jsonb->>\'order\')::jsonb->>\'id\')::integer'
                . ') THEN NULL '
                . 'ELSE ((wi.data::jsonb->>\'order\')::jsonb->>\'id\')::integer END'
                . ') END)'
                . ' FROM oro_workflow_item wi'
                . ' WHERE wi.entity_class = :entityClass AND wi.entity_id::integer = oro_checkout.id',
                ['entityClass' => Checkout::class],
                ['entityClass' => Types::TEXT]
            )
        );
    }
}
