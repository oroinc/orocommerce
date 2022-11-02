<?php

namespace Oro\Bundle\PaymentTermBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreatePaymentTermShortLabelTable implements Migration
{
    /**
     * @throws SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroPaymentTermShortLabelTable($schema);
        $this->addOroPaymentTermShortLabelForeignKeys($schema);
    }

    private function createOroPaymentTermShortLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_payment_term_short_label');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_payment_term_short_label_transport_id', []);
        $table->addUniqueIndex(['localized_value_id'], 'oro_payment_term_short_label_localized_value_id', []);
    }

    /**
     * @throws SchemaException
     */
    private function addOroPaymentTermShortLabelForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_payment_term_short_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
