<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Adds "additional_data" column to "oro_checkout" table and migrate it's values from associated WorkflowItem if any.
 */
class AddAdditionalDataToCheckout implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_checkout');
        if ($table->hasColumn('additional_data')) {
            return;
        }
        $table->addColumn('additional_data', 'text', ['notnull' => false]);

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_checkout SET additional_data = wi.data::jsonb->>\'additional_data\''
                . ' FROM oro_workflow_item wi'
                . ' WHERE wi.entity_class = :entityClass AND wi.entity_id::integer = oro_checkout.id',
                ['entityClass' => Checkout::class],
                ['entityClass' => Types::TEXT]
            )
        );
    }
}
