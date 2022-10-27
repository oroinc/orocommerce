<?php

namespace Oro\Bundle\MoneyOrderBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OroMoneyOrderBundleInstaller implements Installation, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // update system configuration for installed instances
        if ($this->container->get(ApplicationState::class)->isInstalled()) {
            $queries->addPostQuery(new RenameConfigSectionQuery('orob2b_money_order', 'oro_money_order'));
        }

        $this->createOroMoneyOrderTransportLabelTable($schema);
        $this->addOroMoneyOrderTransportLabelForeignKeys($schema);
        $this->updateOroIntegrationTransportTable($schema);

        $this->createOroMoneyOrderShortLabelTable($schema);
        $this->addOroMoneyOrderShortLabelForeignKeys($schema);
    }

    private function createOroMoneyOrderTransportLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_money_order_trans_label');

        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);

        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_money_order_trans_label_transport_id', []);
        $table->addUniqueIndex(['localized_value_id'], 'oro_money_order_trans_label_localized_value_id', []);
    }

    /**
     * @throws SchemaException
     */
    private function addOroMoneyOrderTransportLabelForeignKeys(Schema $schema)
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

    /**
     * @throws SchemaException
     */
    private function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');

        $table->addColumn('money_order_pay_to', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('money_order_send_to', 'text', ['notnull' => false]);
    }

    private function createOroMoneyOrderShortLabelTable(Schema $schema)
    {
        $table = $schema->createTable('oro_money_order_short_label');

        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);

        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addIndex(['transport_id'], 'oro_money_order_short_label_transport_id', []);
        $table->addUniqueIndex(['localized_value_id'], 'oro_money_order_short_label_localized_value_id', []);
    }
    /**
     * @throws SchemaException
     */
    private function addOroMoneyOrderShortLabelForeignKeys(Schema $schema)
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
