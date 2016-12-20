<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateNewTables implements Migration, OrderedMigrationInterface, ActivityExtensionAwareInterface
{
    /**
     * @var ActivityExtension
     */
    private $activityExtension;

    /**
     * @inheritDoc
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * @return int
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
        $this->createOroShipMethodConfigsRuleTable($schema);
        $this->createOroShipMethodPostalCodeTable($schema);

        $queries->addPostQuery(new ExportDataQuery());
    }

    /**
     * @param Schema $schema
     */
    private function createOroShipMethodConfigsRuleTable(Schema $schema)
    {
        $tableName = 'oro_ship_method_configs_rule';

        $table = $schema->createTable('oro_ship_method_configs_rule');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => false]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);

        $table->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rule'),
            ['rule_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', $tableName);
    }

    /**
     * @param Schema $schema
     */
    private function createOroShipMethodPostalCodeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_ship_method_postal_code');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('name', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('destination_id', 'integer', ['notnull' => true]);

        $table->setPrimaryKey(['id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_shipping_rule_destination'),
            ['destination_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
