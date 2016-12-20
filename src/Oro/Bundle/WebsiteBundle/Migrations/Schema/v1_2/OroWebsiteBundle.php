<?php

namespace Oro\Bundle\WebsiteBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroWebsiteBundle implements Migration, ActivityExtensionAwareInterface
{
    const WEBSITE_TABLE_NAME = 'orob2b_website';

    /**
     * @var ActivityExtension
     */
    protected $activityExtension;

    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addIndexForCreateAndUpdateFields($schema);
        $this->addNoteAssociations($schema);
        $this->allowNullOnUrl($schema);
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addIndexForCreateAndUpdateFields(Schema $schema)
    {
        $table = $schema->getTable(self::WEBSITE_TABLE_NAME);
        $table->addIndex(['created_at'], 'idx_orob2b_website_created_at', []);
        $table->addIndex(['updated_at'], 'idx_orob2b_website_updated_at', []);
    }

    /**
     * @param Schema $schema
     */
    protected function addNoteAssociations(Schema $schema)
    {
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', self::WEBSITE_TABLE_NAME);
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function allowNullOnUrl(Schema $schema)
    {
        $table = $schema->getTable(self::WEBSITE_TABLE_NAME);
        $table->getColumn('url')->setNotnull(false);
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }
}
