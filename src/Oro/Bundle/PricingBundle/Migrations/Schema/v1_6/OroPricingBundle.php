<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPricingBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_price_rule');
        $table->addColumn('quantity_expression', 'text', ['notnull' => false]);
        $table->addColumn('currency_expression', 'text', ['notnull' => false]);
        $table->addColumn('product_unit_expression', 'text', ['notnull' => false]);

        $this->createOroNotificationMessageTable($schema);
    }

    /**
     * Create oro_notification_message table
     *
     * @param Schema $schema
     */
    protected function createOroNotificationMessageTable(Schema $schema)
    {
        $table = $schema->createTable('oro_notification_message');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('message', 'text', []);
        $table->addColumn('message_status', 'string', ['length' => 255]);
        $table->addColumn('channel', 'string', ['length' => 255]);
        $table->addColumn('receiver_entity_fqcn', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('receiver_entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_resolved', 'boolean', []);
        $table->addColumn('resolved_at', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('topic', 'string', ['length' => 255]);
        $table->addIndex(['channel', 'topic'], 'oro_notif_msg_channel', []);
        $table->addIndex(['receiver_entity_fqcn', 'receiver_entity_id'], 'oro_notif_msg_entity', []);
        $table->setPrimaryKey(['id']);
    }
}
