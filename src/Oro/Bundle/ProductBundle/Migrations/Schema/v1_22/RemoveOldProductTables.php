<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveOldProductTables implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 30;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        if (!$schema->hasTable('oro_product_prod_name') || !$schema->hasTable('oro_product_name')) {
            return;
        }

        $queries->addPreQuery(<<<SQL
            DELETE FROM oro_fallback_localization_val WHERE id IN (
                SELECT localized_value_id FROM oro_product_name
            )
        SQL);

        $queries->addPreQuery(<<<SQL
            DELETE FROM oro_fallback_localization_val WHERE id IN (
                SELECT localized_value_id FROM oro_product_short_desc
            )
        SQL);

        $queries->addPreQuery(<<<SQL
            DELETE FROM oro_fallback_localization_val WHERE id IN (
                SELECT localized_value_id FROM oro_product_description
            )
        SQL);

        $schema->dropTable('oro_product_name');
        $schema->dropTable('oro_product_short_desc');
        $schema->dropTable('oro_product_description');
    }
}
