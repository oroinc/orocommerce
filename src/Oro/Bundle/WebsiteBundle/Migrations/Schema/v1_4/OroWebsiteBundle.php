<?php

namespace Oro\Bundle\WebsiteBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\ConfigBundle\Migration\RenameConfigSettingsQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\WebsiteBundle\DependencyInjection\Configuration;

class OroWebsiteBundle implements Migration, DatabasePlatformAwareInterface, RenameExtensionAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->changeLocalizationRelations($queries);
        $this->updateWebsiteTable($schema, $queries);
        $this->renameTables($schema, $queries);
        $this->renameSystemConfigurationSettings($queries, [Configuration::URL, Configuration::SECURE_URL]);
    }

    /**
     * @param QueryBag $queries
     */
    private function changeLocalizationRelations(QueryBag $queries)
    {
        $queries->addPreQuery(
            new CopyLocalizationReferencesToConfigQuery()
        );

        $queries->addQuery('DROP TABLE orob2b_websites_localizations');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameTables(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // notes
        $notes = $schema->getTable('oro_note');

        $notes->removeForeignKey('FK_BA066CE1271A24E0');
        $extension->renameColumn($schema, $queries, $notes, 'website_63ea35fe_id', 'website_eb2ef553_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_website',
            ['website_eb2ef553_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\WebsiteBundle\Entity\Website',
            'website_63ea35fe',
            'website_eb2ef553',
            RelationType::MANY_TO_ONE
        ));

        // rename tables
        $extension->renameTable($schema, $queries, 'orob2b_related_website', 'oro_related_website');
        $extension->renameTable($schema, $queries, 'orob2b_website', 'oro_website');

        // rename indexes
        $schema->getTable('orob2b_website')->dropIndex('idx_orob2b_website_created_at');
        $schema->getTable('orob2b_website')->dropIndex('idx_orob2b_website_updated_at');

        $extension->addIndex($schema, $queries, 'oro_website', ['created_at'], 'idx_oro_website_created_at');
        $extension->addIndex($schema, $queries, 'oro_website', ['updated_at'], 'idx_oro_website_updated_at');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function updateWebsiteTable(Schema $schema, QueryBag $queries)
    {
        $this->addIsDefaultColumn($schema, $queries);
        $this->moveUrlToConfigValue($queries);
        $table = $schema->getTable('orob2b_website');
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
    private function addIsDefaultColumn(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orob2b_website');
        $table->addColumn('is_default', 'boolean', ['notnull' => false]);

        $queries->addQuery(
            new ParametrizedSqlMigrationQuery(
                'UPDATE orob2b_website SET is_default = :is_default',
                ['is_default' => false],
                ['is_default' => Type::BOOLEAN]
            )
        );

        if ($this->platform instanceof MySqlPlatform) {
            $queries->addQuery(
                new ParametrizedSqlMigrationQuery(
                    'UPDATE orob2b_website SET is_default = :is_default ORDER BY id ASC LIMIT 1',
                    ['is_default' => true],
                    ['is_default' => Type::BOOLEAN]
                )
            );
        } else {
            $queries->addQuery(
                new ParametrizedSqlMigrationQuery(
                    'UPDATE orob2b_website SET is_default = :is_default WHERE id =(SELECT MIN(id) FROM orob2b_website)',
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
    private function getSchemaDiff(Schema $schema, Schema $toSchema)
    {
        $comparator = new Comparator();

        return $comparator->compare($schema, $toSchema)->toSql($this->platform);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     * @throws SchemaException
     */
    private function doPostUpdateChanges(Schema $schema, QueryBag $queries)
    {
        $postSchema = clone $schema;
        $postSchema->getTable('orob2b_website')
            ->changeColumn('is_default', ['notnull' => true]);
        $postQueries = $this->getSchemaDiff($schema, $postSchema);

        foreach ($postQueries as $query) {
            $queries->addPostQuery($query);
        }
    }

    /**
     * @param QueryBag $queries
     */
    private function moveUrlToConfigValue(QueryBag $queries)
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
                JOIN orob2b_website w ON w.id = oc.record_id
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

    /**
     * @param QueryBag $queries
     * @param array $settings
     */
    private function renameSystemConfigurationSettings(QueryBag $queries, array $settings)
    {
        foreach ($settings as $name) {
            $queries->addPostQuery(new RenameConfigSettingsQuery("oro_b2b_website.$name", "oro_website.$name"));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
