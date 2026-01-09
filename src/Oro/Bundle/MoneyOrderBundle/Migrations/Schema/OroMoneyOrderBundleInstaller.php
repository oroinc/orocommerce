<?php

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class OroMoneyOrderBundleInstaller implements Installation, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_1';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        // update system configuration for installed instances
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            $queries->addPostQuery(new RenameConfigSectionQuery('orob2b_money_order', 'oro_money_order'));
        }

        $this->updateOroIntegrationTransportTable($schema);

        $this->createOroMoneyOrderTransportLabelTable($schema);
        $this->createOroMoneyOrderShortLabelTable($schema);

        $this->addOroMoneyOrderTransportLabelForeignKeys($schema);
        $this->addOroMoneyOrderShortLabelForeignKeys($schema);
    }

    private function updateOroIntegrationTransportTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('money_order_pay_to', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('money_order_send_to', 'text', ['notnull' => false]);
    }

    private function createOroMoneyOrderTransportLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_money_order_trans_label');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_money_order_trans_label_transport_id');
        $table->addUniqueIndex(['localized_value_id'], 'oro_money_order_trans_label_localized_value_id');
    }

    private function addOroMoneyOrderTransportLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_money_order_trans_label');
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

    private function createOroMoneyOrderShortLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_money_order_short_label');
        $table->addColumn('transport_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_money_order_short_label_transport_id');
        $table->addUniqueIndex(['localized_value_id'], 'oro_money_order_short_label_localized_value_id');
    }

    private function addOroMoneyOrderShortLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_money_order_short_label');
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
