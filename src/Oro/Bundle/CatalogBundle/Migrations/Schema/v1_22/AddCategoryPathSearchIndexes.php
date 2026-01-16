<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_22;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add two indexes to optimize default title-based path searches (e.g. "A / B / C / D")
 * when the query filters by organization_id, tree_root, and tree_level for the leaf.
 *
 * They allow PostgreSQL to:
 * - find the leaf candidate(s) via a selective index lookup, and
 * - resolve each parent join via an index lookup on (parent_id, title) within the same tree/organization,
 *   rather than scanning/filtering a larger set of rows.
 *
 * Specifically:
 *  - [organization_id, tree_root, title, tree_level] - leaf lookup
 *  - [organization_id, tree_root, parent_id, title] - parent join lookups
 *
 * In the intended plan, the cost is approximately:
 *   1 index lookup for the leaf
 *   + depth × (1 index lookup per parent join)
 *
 * @see \Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository::findByTitlesPathQueryBuilder()
 */
class AddCategoryPathSearchIndexes implements Migration
{
    /** {@inheritdoc} */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_catalog_category');

        $table->addIndex(
            ['organization_id', 'tree_root', 'parent_id', 'title'],
            'idx_oro_category_org_tree_root_parent_title'
        );
        $table->addIndex(
            ['organization_id', 'tree_root', 'title', 'tree_level'],
            'idx_oro_category_org_tree_root_title_level'
        );
    }
}
