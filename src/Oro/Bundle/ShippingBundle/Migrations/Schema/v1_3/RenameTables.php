<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTables implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    use RenameExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getOrder(): int
    {
        return 10;
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->renameShippingRuleMethodTypeConfig($schema, $queries);
        $this->renameShippingRuleMethodConfig($schema, $queries);
        $this->renameShippingDestination($queries);
    }

    private function renameShippingRuleMethodConfig(Schema $schema, QueryBag $queries): void
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_shipping_rule_mthd_config',
            'oro_ship_method_config'
        );
        $this->addDeleteFormEntityConfigQuery(
            $queries,
            'Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig'
        );
    }

    private function renameShippingRuleMethodTypeConfig(Schema $schema, QueryBag $queries): void
    {
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_shipping_rule_mthd_tp_cnfg',
            'oro_ship_method_type_config'
        );
        $this->addDeleteFormEntityConfigQuery(
            $queries,
            'Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig'
        );
    }

    private function renameShippingDestination(QueryBag $queries): void
    {
        $this->addDeleteFormEntityConfigQuery(
            $queries,
            'Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination'
        );
    }

    private function addDeleteFormEntityConfigQuery(QueryBag $queries, string $className): void
    {
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class_name',
                ['class_name' => $className],
                ['class_name' => Types::STRING]
            )
        );
    }
}
