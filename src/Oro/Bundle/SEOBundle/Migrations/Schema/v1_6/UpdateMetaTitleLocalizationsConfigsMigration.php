<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Entity\Brand;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;

class UpdateMetaTitleLocalizationsConfigsMigration implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->moveLocalizationTextToString($queries);
        $this->updateEntityFieldsConfig($queries);
    }

    private function moveLocalizationTextToString(QueryBag $queries)
    {
        // product meta titles, set string field
        $queries->addPostQuery(
            "UPDATE oro_fallback_localization_val AS f SET text = NULL, string = LEFT(text,255)
            WHERE text IS NOT NULL AND string IS NULL
            AND (
                EXISTS(SELECT * FROM oro_rel_1cf73d3121a159aea3971e AS t1 WHERE t1.localizedfallbackvalue_id = f.id) OR
                EXISTS(SELECT * FROM oro_rel_ff3a7b9721a159aea3971e AS t2 WHERE t2.localizedfallbackvalue_id = f.id) OR
                EXISTS(SELECT * FROM oro_rel_b438191e21a159aea3971e AS t3 WHERE t3.localizedfallbackvalue_id = f.id) OR
                EXISTS(SELECT * FROM oro_rel_5b5187a321a159aea3971e AS t4 WHERE t4.localizedfallbackvalue_id = f.id) OR
                EXISTS(SELECT * FROM oro_rel_dd93d65c21a159aea3971e AS t5 WHERE t5.localizedfallbackvalue_id = f.id)
            );"
        );
        // product meta titles, set text field to null
        $queries->addPostQuery(
            "UPDATE oro_fallback_localization_val AS f SET text = NULL 
            WHERE text IS NOT NULL AND string IS NOT NULL 
            AND (
                EXISTS(SELECT * FROM oro_rel_1cf73d3121a159aea3971e AS t1 WHERE t1.localizedfallbackvalue_id = f.id) OR
                EXISTS(SELECT * FROM oro_rel_ff3a7b9721a159aea3971e AS t2 WHERE t2.localizedfallbackvalue_id = f.id) OR
                EXISTS(SELECT * FROM oro_rel_b438191e21a159aea3971e AS t3 WHERE t3.localizedfallbackvalue_id = f.id) OR
                EXISTS(SELECT * FROM oro_rel_5b5187a321a159aea3971e AS t4 WHERE t4.localizedfallbackvalue_id = f.id) OR
                EXISTS(SELECT * FROM oro_rel_dd93d65c21a159aea3971e AS t5 WHERE t5.localizedfallbackvalue_id = f.id)
            );"
        );
    }

    private function updateEntityFieldsConfig(QueryBag $queries)
    {
        foreach ([Product::class, Category::class, Page::class, ContentNode::class, Brand::class] as $className) {
            $queries->addQuery(
                new UpdateEntityConfigFieldValueQuery(
                    $className,
                    'metaTitles',
                    'importexport',
                    'fallback_field',
                    'string'
                )
            );
        }
    }
}
