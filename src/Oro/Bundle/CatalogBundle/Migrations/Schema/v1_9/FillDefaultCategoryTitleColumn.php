<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class FillDefaultCategoryTitleColumn implements
    Migration,
    DatabasePlatformAwareInterface,
    OrderedMigrationInterface
{
    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 20;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($this->platform instanceof PostgreSqlPlatform) {
            $this->doPostgreSql($queries);
        } else {
            $this->doMySql($queries);
        }
    }

    /**
     * Fill default category title column with value.
     */
    private function doPostgreSql(QueryBag $queries)
    {
        $queries->addPreQuery(
            new SqlMigrationQuery(
                <<<SQL
UPDATE oro_catalog_category
SET title = subquery.title
FROM (
         SELECT f.string as title, ct.category_id as category_id
         FROM oro_fallback_localization_val f
             INNER JOIN oro_catalog_category_title ct
                 ON f.id = ct.localized_value_id
                    AND f.localization_id IS NULL
     ) as subquery
WHERE subquery.category_id = id
SQL
            )
        );
    }

    /**
     * Fill default category title column with value.
     */
    private function doMySql(QueryBag $queries)
    {
        $queries->addPreQuery(
            new SqlMigrationQuery(
                <<<SQL
UPDATE oro_catalog_category AS c, 
    (
        SELECT f.string as title, ct.category_id as category_id
        FROM oro_fallback_localization_val f
        INNER JOIN oro_catalog_category_title ct
        ON f.id = ct.localized_value_id
        AND f.localization_id IS NULL
    ) as subquery
SET c.title = subquery.title
WHERE subquery.category_id = c.id
SQL
            )
        );
    }
}
