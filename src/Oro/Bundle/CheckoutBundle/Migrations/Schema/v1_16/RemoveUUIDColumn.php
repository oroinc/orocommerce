<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Delete uuid field that does not exist in the Checkout entity.
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
        $table = $schema->getTable('oro_checkout');
        if ($table->hasIndex('UNIQ_C040FD59D17F50A6')) {
            $table->dropIndex('UNIQ_C040FD59D17F50A6');
        }

        if ($table->hasIndex('oro_checkout_uuid')) {
            $table->dropIndex('oro_checkout_uuid');
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
                ['class' => Checkout::class, 'fieldName' => 'uuid'],
                ['class' => Types::STRING, 'fields' => Types::STRING]
            )
        );
    }
}
