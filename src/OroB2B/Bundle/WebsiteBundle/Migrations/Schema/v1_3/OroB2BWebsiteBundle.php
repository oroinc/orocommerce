<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Comparator;

use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class OroB2BWebsiteBundle implements
    Migration,
    RenameExtensionAwareInterface,
    NameGeneratorAwareInterface,
    DatabasePlatformAwareInterface
{
    /** @var AbstractPlatform */
    protected $platform;

    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

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
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateLocaleTable($schema, $queries);
        $this->updateWebsiteTable($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function updateLocaleTable(Schema $schema, QueryBag $queries)
    {
        $queries->addQuery(
            'INSERT INTO oro_localization ' .
            '(id, parent_id, name, language_code, formatting_code, created_at, updated_at) ' .
            'SELECT id, parent_id, title, code, code, created_at, updated_at FROM orob2b_locale'
        );
        
        if ($this->platform instanceof PostgreSqlPlatform) {
            $queries->addQuery(
                "SELECT setval(pg_get_serial_sequence('oro_localization', 'id'), max(id)) " .
                "FROM oro_localization"
            );
        }

        $this->dropConstraint($schema, 'orob2b_websites_locales', ['website_id']);
        $this->dropConstraint($schema, 'orob2b_websites_locales', ['locale_id']);

        $preSchema = clone $schema;
        $preSchema->dropTable('orob2b_locale');

        foreach ($this->getSchemaDiff($schema, $preSchema) as $query) {
            $queries->addQuery($query);
        }
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function updateWebsiteTable(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_websites_locales');

        $this->renameExtension->renameColumn($schema, $queries, $table, 'locale_id', 'localization_id');
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'orob2b_websites_locales',
            'orob2b_websites_localizations'
        );

        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_websites_localizations',
            'oro_localization',
            ['localization_id']
        );
        $this->createConstraint(
            $schema,
            $queries,
            'orob2b_websites_localizations',
            'orob2b_website',
            ['website_id']
        );
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     * @param array $fields
     */
    protected function dropConstraint(Schema $schema, $tableName, array $fields)
    {
        $indexName = $this->nameGenerator->generateIndexName($tableName, $fields, false, true);
        $constraintName = $this->nameGenerator->generateForeignKeyConstraintName($tableName, $fields, true);

        $table = $schema->getTable($tableName);
        $table->dropIndex($indexName);
        $table->removeForeignKey($constraintName);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @param string $tableName
     * @param string $foreignTable
     * @param array $fields
     */
    protected function createConstraint(Schema $schema, QueryBag $queries, $tableName, $foreignTable, array $fields)
    {
        $indexName = $this->nameGenerator->generateIndexName($tableName, $fields, false, true);

        $this->renameExtension->addIndex($schema, $queries, $tableName, $fields, $indexName);
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            $tableName,
            $foreignTable,
            $fields,
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
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
}
