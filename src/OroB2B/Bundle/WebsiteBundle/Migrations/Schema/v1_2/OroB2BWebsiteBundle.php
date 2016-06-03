<?php

namespace OroB2B\Bundle\WebsiteBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroB2BWebsiteBundle implements Migration, NoteExtensionAwareInterface
{
    const WEBSITE_TABLE_NAME = 'orob2b_website';

    /** @var NoteExtension */
    protected $noteExtension;

    /**
     * @inheritDoc
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

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
        $this->noteExtension->addNoteAssociation($schema, self::WEBSITE_TABLE_NAME);
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
}
