<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BOrderBundle implements Migration, NoteExtensionAwareInterface
{
    const ORDER_TABLE_NAME = 'orob2b_order';

    /** @var  NoteExtension */
    protected $noteExtension;

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        self::addNoteAssociations($schema, $this->noteExtension);
    }

    /**
     * Enable notes for Order entity
     *
     * @param Schema $schema
     */
    public static function addNoteAssociations(Schema $schema, NoteExtension $noteExtension)
    {
        $noteExtension->addNoteAssociation($schema, self::ORDER_TABLE_NAME);
    }
}