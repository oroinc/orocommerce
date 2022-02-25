<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_17;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateCategoryTablesData implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder(): int
    {
        return 20;
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

        $queries->addQuery('INSERT INTO oro_catalog_cat_title (fallback, string, localization_id, category_id)
            SELECT oflv.fallback, oflv.string, oflv.localization_id, occt.category_id
            FROM oro_fallback_localization_val oflv
            INNER JOIN oro_catalog_category_title occt ON occt.localized_value_id = oflv.id');

        $queries->addQuery('INSERT INTO oro_catalog_cat_s_descr (fallback, text, localization_id, category_id)
            SELECT oflv.fallback, oflv.text, oflv.localization_id, occsd.category_id
            FROM oro_fallback_localization_val oflv
            INNER JOIN oro_catalog_cat_short_desc occsd ON occsd.localized_value_id = oflv.id');

        $hasWysiwygColumn = $schema->getTable('oro_fallback_localization_val')->hasColumn('wysiwyg');

        $queries->addQuery(
            sprintf(
                'INSERT INTO oro_catalog_cat_l_descr (
                    fallback, wysiwyg, wysiwyg_style, wysiwyg_properties, localization_id, category_id
                )
                SELECT oflv.fallback, %soflv.localization_id, occld.category_id
                FROM oro_fallback_localization_val oflv
                INNER JOIN oro_catalog_cat_long_desc occld ON occld.localized_value_id = oflv.id',
                $hasWysiwygColumn
                    ? 'oflv.wysiwyg, oflv.wysiwyg_style, oflv.wysiwyg_properties, '
                    : 'oflv.text, null, null, '
            )
        );
    }
}
