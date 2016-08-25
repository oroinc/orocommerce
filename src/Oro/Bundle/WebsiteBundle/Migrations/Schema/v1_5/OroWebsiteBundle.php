<?php

namespace Oro\Bundle\WebsiteBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\WebsiteBundle\Migrations\Schema\OroWebsiteBundleInstaller;

class OroWebsiteBundle implements Migration, DatabasePlatformAwareInterface
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
        $table = $schema->getTable(OroWebsiteBundleInstaller::WEBSITE_TABLE_NAME);
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
        $table = $schema->getTable(OroWebsiteBundleInstaller::WEBSITE_TABLE_NAME);
        $table->addColumn('is_default', 'boolean', ['notnull' => false]);

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE oro_website SET is_default = :is_default',
                ['is_default' => false],
                ['is_default' => Type::BOOLEAN]
            )
        );

        if ($this->platform instanceof MySqlPlatform) {
            $queries->addQuery(
                new ParametrizedSqlMigrationQuery(
                    'UPDATE oro_website SET is_default = :is_default ORDER BY id ASC LIMIT 1',
                    ['is_default' => true],
                    ['is_default' => Type::BOOLEAN]
                )
            );
        } else {
            $queries->addQuery(
                new ParametrizedSqlMigrationQuery(
                    'UPDATE oro_website SET is_default = :is_default WHERE id =(SELECT MIN(id) FROM oro_website)',
                    ['is_default' => true],
                    ['is_default' => Type::BOOLEAN]
                )
            );
        }


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
        $postSchema->getTable(OroWebsiteBundleInstaller::WEBSITE_TABLE_NAME)
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
            SELECT :entity_name, id FROM oro_website w
            WHERE NOT exists(SELECT record_id FROM oro_config oc WHERE oc.record_id = w.id AND oc.entity = 'website');",
                ['entity_name' => 'website'],
                ['entity_name' => Type::STRING]
            )
        );
        $queries->addPreQuery($this->getConfigInsertQuery('url'));
    }

    /**
     * @param string $name
     * @return ParametrizedSqlMigrationQuery
     */
    private function getConfigInsertQuery($name)
    {
        $now = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'));
        return new ParametrizedSqlMigrationQuery(
            'INSERT INTO oro_config_value (
                        config_id, name, section, text_value, object_value, array_value, type, created_at, updated_at
                    )
                SELECT
                  oc.id,
                  :name,
                  :section,
                  CASE WHEN w.url = :default_url THEN :new_default_url ELSE w.url END,
                  :object_value,
                  :array_value,
                  :type,
                  :created_at,
                  :updated_at
                FROM oro_config oc
                JOIN oro_website w ON w.id = oc.record_id
                WHERE entity = :entity;',
            [
                'name' => $name,
                'section' => 'oro_b2b_website',
                'object_value' => null,
                'array_value' => null,
                'type' => 'scalar',
                'created_at' => $now,
                'updated_at' => $now,
                'entity' => 'website',
                'default_url' => 'http://localhost/oro/',
                'new_default_url' => 'http://localhost/'
            ],
            [
                'name' => Type::STRING,
                'section' => Type::STRING,
                'object_value' => Type::OBJECT,
                'array_value' => Type::TARRAY,
                'type' => Type::STRING,
                'created_at' => Type::DATETIME,
                'updated_at' => Type::DATETIME,
                'entity' => Type::STRING,
                'default_url' => Type::STRING,
                'new_default_url' => Type::STRING,
            ]
        );
    }
}
