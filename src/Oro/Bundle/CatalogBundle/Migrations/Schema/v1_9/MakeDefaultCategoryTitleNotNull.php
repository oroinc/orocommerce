<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class MakeDefaultCategoryTitleNotNull implements
    Migration,
    OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 30;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->getTable('oro_catalog_category')
            ->getColumn('title')->setNotnull(true);
    }
}
