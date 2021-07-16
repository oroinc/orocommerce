<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RenameTables implements Migration, RenameExtensionAwareInterface, OrderedMigrationInterface
{
    /** @internal */
    const SHIPPING_RULE_METHOD_CONFIG_CLASS_NAME = 'Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig';

    /** @internal */
    const SHIPPING_RULE_METHOD_TYPE_CONFIG_CLASS_NAME = 'Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig';

    /** @internal */
    const SHIPPING_RULE_DESTINATION_CLASS_NAME = 'Oro\Bundle\ShippingBundle\Entity\ShippingRuleDestination';

    /** @var RenameExtension */
    private $renameExtension;

    /**
     * @return int
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameShippingRuleMethodTypeConfig($schema, $queries);
        $this->renameShippingRuleMethodConfig($schema, $queries);
        $this->renameShippingDestination($queries);
    }

    private function renameShippingRuleMethodConfig(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension
            ->renameTable($schema, $queries, 'oro_shipping_rule_mthd_config', 'oro_ship_method_config');
        $this->addDeleteFormEntityConfigQuery($queries, static::SHIPPING_RULE_METHOD_CONFIG_CLASS_NAME);
    }

    private function renameShippingRuleMethodTypeConfig(Schema $schema, QueryBag $queries)
    {
        $this->renameExtension
            ->renameTable($schema, $queries, 'oro_shipping_rule_mthd_tp_cnfg', 'oro_ship_method_type_config');
        $this->addDeleteFormEntityConfigQuery($queries, static::SHIPPING_RULE_METHOD_TYPE_CONFIG_CLASS_NAME);
    }

    private function renameShippingDestination(QueryBag $queries)
    {
        $this->addDeleteFormEntityConfigQuery($queries, static::SHIPPING_RULE_DESTINATION_CLASS_NAME);
    }

    /**
     * @param QueryBag $queries
     * @param string $className
     */
    private function addDeleteFormEntityConfigQuery(QueryBag $queries, $className)
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
