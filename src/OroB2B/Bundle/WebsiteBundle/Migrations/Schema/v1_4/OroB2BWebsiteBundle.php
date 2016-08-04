<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Schema\v1_4;

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
                sprintf(
                    'UPDATE %s SET is_default = :is_default',
                    OroB2BWebsiteBundleInstaller::WEBSITE_TABLE_NAME
                ),
                ['is_default' => false],
                ['is_default' => Type::BOOLEAN]
            )
        );
        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                sprintf(
                    'UPDATE %s SET is_default = :is_default WHERE id = (SELECT MIN(id) FROM %s)',
                    OroB2BWebsiteBundleInstaller::WEBSITE_TABLE_NAME,
                    OroB2BWebsiteBundleInstaller::WEBSITE_TABLE_NAME
                ),
                ['is_default' => Type::BOOLEAN, 'id' => Type::INTEGER]
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
}
