<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use OroB2B\Bundle\WebsiteBundle\Migrations\Schema\OroB2BWebsiteBundleInstaller;

class OroB2BWebsiteBundle implements Migration, DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateWebsiteTable($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function updateWebsiteTable(Schema $schema, QueryBag $queries)
    {
        $this->addIsDefaultColumn($schema, $queries);
        $this->moveUrlToConfigValue($queries);
        $table = $schema->getTable(OroB2BWebsiteBundleInstaller::WEBSITE_TABLE_NAME);
        $table->dropColumn('url');
    }

    /**
     * {@inheritdoc}
     */
    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @throws SchemaException
     */
    protected function addIsDefaultColumn(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable(OroB2BWebsiteBundleInstaller::WEBSITE_TABLE_NAME);
        $table->addColumn('is_default', 'boolean', ['notnull' => false]);

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_website SET is_default = :is_default',
                ['is_default' => false],
                ['is_default' => Type::BOOLEAN]
            )
        );
        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_website SET is_default = :is_default WHERE id = (SELECT MIN(id) FROM orob2b_website)',
                ['is_default' => true],
                ['is_default' => Type::BOOLEAN]
            )
        );

        $this->doPostUpdateChanges($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param Schema $toSchema
     * @return array
     */
    protected function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();

        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @throws SchemaException
     */
    protected function doPostUpdateChanges(Schema $schema, QueryBag $queries)
    {
        $postSchema = clone $schema;
        $postSchema->getTable(OroB2BWebsiteBundleInstaller::WEBSITE_TABLE_NAME)
            ->changeColumn('is_default', ['notnull' => true]);
        $postQueries = $this->getSchemaDiff($schema, $postSchema);

        foreach ($postQueries as $query) {
            $queries->addPostQuery($query);
        }
    }

    /**
     * @param QueryBag $queries
     */
    protected function moveUrlToConfigValue(QueryBag $queries)
    {
        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                "INSERT INTO oro_config (entity, record_id)
            SELECT :entity_name, id FROM orob2b_website w
            WHERE NOT exists(SELECT record_id FROM oro_config oc WHERE oc.record_id = w.id AND oc.entity = 'website');",
                ['entity_name' => 'website'],
                ['entity_name' => Type::STRING]
            )
        );
        $queries->addPreQuery($this->getConfigInsertQuery('url'));
        $queries->addPreQuery($this->getConfigInsertQuery('secure_url'));
    }

    /**
     * @param string $name
     * @return ParametrizedSqlMigrationQuery
     */
    private function getConfigInsertQuery($name)
    {
        $now = new \DateTime();
        return new ParametrizedSqlMigrationQuery(
            "INSERT INTO oro_config_value (
                        config_id, name, section, text_value, object_value, array_value, type, created_at, updated_at
                    )
                SELECT
                  oc.id,
                  :name,
                  :section,
                  CASE WHEN w.url = 'http://localhost/oro/' THEN 'http://localhost/' ELSE w.url END,
                  :object_value,
                  :array_value,
                  :type,
                  :created_at,
                  :updated_at
                FROM oro_config oc
                JOIN orob2b_website w ON w.id = oc.record_id
                WHERE entity = 'website';",
            [
                'name' => $name,
                'section' => 'oro_b2b_website',
                'object_value' => 'Tjs=',
                'array_value' => 'Tjs=',
                'type' => 'scalar',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => Type::STRING,
                'section' => Type::STRING,
                'object_value' => Type::STRING,
                'array_value' => Type::STRING,
                'type' => Type::STRING,
                'created_at' => Type::DATETIME,
                'updated_at' => Type::DATETIME,
            ]
        );
    }
}
