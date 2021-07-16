<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class FillBrandDefaultTitleMigration implements Migration, DatabasePlatformAwareInterface, OrderedMigrationInterface
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
            $this->applyForPostgreSql($queries);
        } else {
            $this->applyForMySql($queries);
        }
    }

    /**
     * Fills default brand title column with localized value
     */
    private function applyForPostgreSql(QueryBag $queries)
    {
        $queries->addPreQuery(
            new SqlMigrationQuery(
                <<<SQL
UPDATE oro_brand
SET default_title = subquery.title
FROM (
         SELECT OFLV.string as title, OBN.brand_id as brand_id
         FROM oro_fallback_localization_val OFLV
             INNER JOIN oro_brand_name OBN
                    ON OFLV.id = OBN.localized_value_id
                    AND OFLV.localization_id IS NULL
     ) as subquery
WHERE subquery.brand_id = id
SQL
            )
        );
    }

    /**
     * Fills default brand title column with localized value
     */
    private function applyForMySql(QueryBag $queries)
    {
        $queries->addPreQuery(
            new SqlMigrationQuery(
                <<<SQL
UPDATE oro_brand AS OB, 
    (
        SELECT OFLV.string as title, OBN.brand_id as brand_id
        FROM oro_fallback_localization_val OFLV
        INNER JOIN oro_brand_name OBN
        ON OFLV.id = OBN.localized_value_id
        AND OFLV.localization_id IS NULL
    ) as subquery
SET OB.default_title = subquery.title
WHERE subquery.brand_id = OB.id
SQL
            )
        );
    }
}
