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
        $this->moveUrlToConfigValue($schema, $queries);
        $table = $schema->getTable(OroB2BWebsiteBundleInstaller::WEBSITE_TABLE_NAME);
        $table->dropIndex('url');
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
     * @param Schema $schema
     * @param QueryBag $queries
     * @throws SchemaException
     */
    protected function moveUrlToConfigValue(Schema $schema, QueryBag $queries)
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
//        $queries->addPreQuery()
    }
}
