<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds "payment_in_progress" column to "oro_checkout" table
 * and migrate it`s values from associated workflow item if any.
 */
class AddPaymentInProgressToCheckout implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_checkout');
        if ($table->hasColumn('payment_in_progress')) {
            return;
        }

        $table->addColumn('payment_in_progress', 'boolean', ['default' => false]);
        $queries->addPostQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_checkout SET payment_in_progress = '
            // adding cast in reason of payment_in_progress not exists in checkout workflow item data
            // in all steps before payment checkout and oro_checkout.payment_in_progress is not nullable
            . '(CASE WHEN wi.data::jsonb->>\'payment_in_progress\' IS NULL THEN FALSE'
            . ' ELSE (wi.data::jsonb->>\'payment_in_progress\')::boolean END)'
            . ' FROM oro_workflow_item wi'
            . ' WHERE wi.entity_class = :entityClass AND wi.entity_id::integer = oro_checkout.id',
            ['entityClass' => Checkout::class],
            ['entityClass' => Types::TEXT]
        ));
    }
}
