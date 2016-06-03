<?php

namespace OroB2B\Bundle\FallbackBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class OroB2BWebsiteBundle implements Migration, RenameExtensionAwareInterface, NameGeneratorAwareInterface
{
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
        $indexName = $this->nameGenerator->generateIndexName('orob2b_locale', ['parent_id'], false, true);

        $table = $schema->getTable('orob2b_locale');
        $table->addColumn('formatting_code', 'string', ['length' => 64]);
        $table->dropIndex('uniq_orob2b_locale_code');
        $table->dropIndex('uniq_orob2b_locale_title');
        $table->dropIndex($indexName);
        $table->removeForeignKey('fk_orob2b_locale_parent_id');

        $this->renameExtension->renameColumn($schema, $queries, $table, 'title', 'name');
        $this->renameExtension->renameColumn($schema, $queries, $table, 'code', 'language_code');

        $schema->dropTable('oro_localization');
        $this->renameExtension->renameTable($schema, $queries, 'orob2b_locale', 'oro_localization');

        $indexName = $this->nameGenerator->generateIndexName('oro_localization', ['parent_id'], false, true);

        $this->renameExtension->addIndex($schema, $queries, 'oro_localization', ['parent_id'], $indexName);
        $this->renameExtension->addUniqueIndex($schema, $queries, 'oro_localization', ['name']);
        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_localization',
            'oro_localization',
            ['parent_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $queries->addQuery('UPDATE `oro_localization` SET `formatting_code` = `language_code`');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function updateWebsiteTable(Schema $schema, QueryBag $queries)
    {
        $this->dropConstraints($schema, 'orob2b_websites_locales', ['locale_id']);
        $this->dropConstraints($schema, 'orob2b_websites_locales', ['website_id']);

        $table = $schema->getTable('orob2b_websites_locales');
        $this->renameExtension->renameColumn($schema, $queries, $table, 'locale_id', 'localization_id');

        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'orob2b_websites_locales',
            'orob2b_websites_localizations'
        );

        $this->createConstraints(
            $schema,
            $queries,
            'orob2b_websites_localizations',
            'oro_localization',
            ['localization_id']
        );
        $this->createConstraints(
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
    protected function dropConstraints(Schema $schema, $tableName, array $fields)
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
    protected function createConstraints(Schema $schema, QueryBag $queries, $tableName, $foreignTable, array $fields)
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
}
