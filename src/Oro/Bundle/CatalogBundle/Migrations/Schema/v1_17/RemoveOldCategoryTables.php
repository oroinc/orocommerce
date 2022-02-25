<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveOldCategoryTables implements Migration, OrderedMigrationInterface
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
        if (!$schema->hasTable('oro_catalog_cat_title')
            || !$schema->hasTable('oro_catalog_category_title')
        ) {
            return;
        }

        $queries->addPreQuery(<<<SQL
            DELETE FROM oro_fallback_localization_val WHERE id IN (
                SELECT localized_value_id FROM oro_catalog_category_title
            )
        SQL);

        $queries->addPreQuery(<<<SQL
            DELETE FROM oro_fallback_localization_val WHERE id IN (
                SELECT localized_value_id FROM oro_catalog_cat_short_desc
            )
        SQL);

        $queries->addPreQuery(<<<SQL
            DELETE FROM oro_fallback_localization_val WHERE id IN (
                SELECT localized_value_id FROM oro_catalog_cat_long_desc
            )
        SQL);

        $schema->dropTable('oro_catalog_category_title');
        $schema->dropTable('oro_catalog_cat_short_desc');
        $schema->dropTable('oro_catalog_cat_long_desc');
    }
}
