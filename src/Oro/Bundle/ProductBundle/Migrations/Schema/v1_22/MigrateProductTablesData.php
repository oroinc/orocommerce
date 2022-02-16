<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MigrateProductTablesData implements Migration, OrderedMigrationInterface
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
        if (!$schema->hasTable('oro_product_prod_name') || !$schema->hasTable('oro_product_name')) {
            return;
        }

        $queries->addQuery('INSERT INTO oro_product_prod_name (fallback, string, localization_id, product_id)
            SELECT oflv.fallback, oflv.string, oflv.localization_id, opn.product_id
            FROM oro_fallback_localization_val oflv
            INNER JOIN oro_product_name opn ON opn.localized_value_id = oflv.id');

        $queries->addQuery('INSERT INTO oro_product_prod_s_descr (fallback, text, localization_id, product_id)
            SELECT oflv.fallback, oflv.text, oflv.localization_id, opsd.short_description_id
            FROM oro_fallback_localization_val oflv
            INNER JOIN oro_product_short_desc opsd ON opsd.localized_value_id = oflv.id');

        $hasWysiwygColumn = $schema->getTable('oro_fallback_localization_val')->hasColumn('wysiwyg');

        $queries->addQuery(
            sprintf(
                'INSERT INTO oro_product_prod_descr (
                    fallback, wysiwyg, wysiwyg_style, wysiwyg_properties, localization_id, product_id
                )
                SELECT oflv.fallback, %soflv.localization_id, opd.description_id
                FROM oro_fallback_localization_val oflv
                INNER JOIN oro_product_description opd ON opd.localized_value_id = oflv.id',
                $hasWysiwygColumn
                    ? 'oflv.wysiwyg, oflv.wysiwyg_style, oflv.wysiwyg_properties, '
                    : 'oflv.text, null, null, '
            )
        );
    }
}
