<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;

class FillDefaultProductNameColumn implements
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
     * Fill default product name column with value.
     */
    private function doPostgreSql(QueryBag $queries)
    {
        $queries->addPreQuery(
            new SqlMigrationQuery(
                <<<SQL
 UPDATE oro_product 
 SET name = subquery.name, name_uppercase = UPPER(subquery.name)
 FROM (
    SELECT f.string as name, pn.product_id as product_id
    FROM oro_fallback_localization_val f
    INNER JOIN oro_product_name pn
      ON f.id = pn.localized_value_id
      AND f.localization_id IS NULL
 ) as subquery
 WHERE subquery.product_id = id
SQL
            )
        );
    }

    /**
     * Fill default product name column with value.
     */
    private function doMySql(QueryBag $queries)
    {
        $queries->addPreQuery(
            new SqlMigrationQuery(
                <<<SQL
 UPDATE oro_product AS p, (
    SELECT f.string as name, pn.product_id as product_id
    FROM oro_fallback_localization_val f
    INNER JOIN oro_product_name pn
      ON f.id = pn.localized_value_id
      AND f.localization_id IS NULL
 ) as subquery
 SET p.name = subquery.name, p.name_uppercase = UPPER(subquery.name)
 WHERE subquery.product_id = p.id
SQL
            )
        );
    }
}
