<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateRootCategoryTitle implements
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
        return 10;
    }

    /**
     * Update default root category title column with new value.
     *
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        if ($this->platform instanceof PostgreSqlPlatform) {
            $sql = $this->getPostgreSql();
        } else {
            $sql = $this->getMySql();
        }

        $params = [
            'oldTitle' => 'Products categories',
            'categoryId' => 1,
            'newTitle' => 'All Products'
        ];

        $types = [
            'oldTitle' => 'string',
            'categoryId' => 'integer',
            'newTitle' => 'string'
        ];

        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery($sql, $params, $types)
        );
    }

    /**
     * @return string
     */
    private function getPostgreSql()
    {
        return <<<SQL
UPDATE oro_fallback_localization_val
SET string = :newTitle
FROM (
         SELECT f.string as title, f.id as title_id
         FROM oro_fallback_localization_val f
             INNER JOIN oro_catalog_category_title ct
                 ON f.id = ct.localized_value_id
                    AND f.localization_id IS NULL
                    AND ct.category_id = :categoryId
                    AND f.string = :oldTitle
      ) as subquery
WHERE subquery.title_id = id
SQL;
    }

    /**
     * @return string
     */
    private function getMySql()
    {
        return <<<SQL
UPDATE oro_fallback_localization_val AS f
    INNER JOIN oro_catalog_category_title AS ct
        ON f.id = ct.localized_value_id
            AND f.localization_id IS NULL
            AND ct.category_id = :categoryId
            AND f.string = :oldTitle
SET f.string = :newTitle
SQL;
    }
}
