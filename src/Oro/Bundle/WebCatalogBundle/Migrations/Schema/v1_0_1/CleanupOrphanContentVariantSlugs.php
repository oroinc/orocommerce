<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Schema\v1_0_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * This migration cleans wrong Slugs that is left in oro_redirect_slug table after its parent ContentVariant entities
 * are removed via onDelete=CASCADE.
 */
class CleanupOrphanContentVariantSlugs implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery('
             DELETE FROM oro_redirect_slug
             WHERE EXISTS(
                 SELECT 1 FROM oro_slug_scope
                   INNER JOIN oro_scope ON oro_slug_scope.scope_id = oro_scope.id
                 WHERE oro_slug_scope.slug_id = oro_redirect_slug.id
                 AND oro_scope.webcatalog_id IS NOT NULL
             )
             AND NOT EXISTS(
                 SELECT 1 FROM oro_web_catalog_variant_slug
                 WHERE oro_web_catalog_variant_slug.slug_id = oro_redirect_slug.id
             )                    
        ');
    }
}
