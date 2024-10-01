<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * Delete uuid field that does not exist in the Order entity.
 */
class RemoveUUIDColumn implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->removeField($schema);
        $this->removeEntityFieldConfig($queries);
    }

    private function removeField(Schema $schema): void
    {
        $table = $schema->getTable('oro_order');
        if ($table->hasIndex('UNIQ_388B2E9DD17F50A6')) {
            $table->dropIndex('UNIQ_388B2E9DD17F50A6');
        }

        if ($table->hasIndex('oro_order_uuid')) {
            $table->dropIndex('oro_order_uuid');
        }

        if ($table->hasColumn('uuid')) {
            $table->dropColumn('uuid');
        }
    }

    private function removeEntityFieldConfig(QueryBag $queries): void
    {
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config_field WHERE field_name = :fieldName
                  AND entity_id IN (SELECT id FROM oro_entity_config WHERE class_name = :class)',
                ['class' => Order::class, 'fieldName' => 'uuid'],
                ['class' => Types::STRING, 'fields' => Types::STRING]
            )
        );
    }
}
