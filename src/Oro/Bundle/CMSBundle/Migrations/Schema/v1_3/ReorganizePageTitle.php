<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Migrations\Schema\v1_2\DropEntityConfigFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ReorganizePageTitle implements Migration, DatabasePlatformAwareInterface
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

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
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCmsPageTitleTable($schema);

        /** Foreign keys generation **/
        $this->addOroCmsPageTitleForeignKeys($schema);

        $queries->addQuery(new ReorganizeTitleQuery());

        $this->dropTitleColumn($schema, $queries);
    }

    /**
     * Create oro_cms_page_title table
     */
    protected function createOroCmsPageTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_page_title');
        $table->addColumn('page_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['page_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_cms_page_title foreign keys.
     */
    protected function addOroCmsPageTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_page'),
            ['page_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
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

    protected function dropTitleColumn(Schema $schema, QueryBag $queries)
    {
        $preSchema = clone $schema;
        $table = $preSchema->getTable('oro_cms_page');
        $table->dropColumn('title');

        foreach ($this->getSchemaDiff($schema, $preSchema) as $query) {
            $queries->addQuery($query);
        }
        $queries->addQuery(new DropEntityConfigFieldQuery(Page::class, 'title'));
    }
}
