<?php

namespace Oro\Bundle\FixedProductShippingBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroFixedProductBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroFixedProductTransportLabelTable($schema);
        $this->addOroFixedProductTransportLabelForeignKeys($schema);
    }

    private function createOroFixedProductTransportLabelTable(Schema $schema): void
    {
        if (!$schema->hasTable('oro_fixed_product_transp_label')) {
            $table = $schema->createTable('oro_fixed_product_transp_label');
            $table->addColumn('transport_id', 'integer', []);
            $table->addColumn('localized_value_id', 'integer', []);
            $table->addUniqueIndex(['localized_value_id'], 'oro_fixed_product_transp_label_localized_value_id');
            $table->setPrimaryKey(['transport_id', 'localized_value_id']);
            $table->addIndex(['transport_id'], 'oro_fixed_product_transp_label_transport_id', []);
        }
    }

    private function addOroFixedProductTransportLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_fixed_product_transp_label');
        if (!$table->hasForeignKey('transport_id')) {
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_integration_transport'),
                ['transport_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }

        if (!$table->hasForeignKey('localized_value_id')) {
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_fallback_localization_val'),
                ['localized_value_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }
    }
}
