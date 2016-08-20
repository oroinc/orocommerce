<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class RenameTablesAndColumns implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // email to request association
        $extension->renameTable($schema, $queries, 'oro_rel_26535370f42ab603f15753', 'oro_rel_26535370f42ab603ec4b1d');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\RFPBundle\Entity\Request',
            'request_9fd4910b',
            'request_d1d045e1',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to request association
        $extension->renameTable($schema, $queries, 'oro_rel_46a29d19f42ab603f15753', 'oro_rel_46a29d19f42ab603ec4b1d');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\RFPBundle\Entity\Request',
            'request_9fd4910b',
            'request_d1d045e1',
            RelationType::MANY_TO_MANY
        ));

        // notes
        $notes = $schema->getTable('oro_note');

        $notes->removeForeignKey('FK_BA066CE1EF5A4BE2');
        $extension->renameColumn($schema, $queries, $notes, 'request_86063709_id', 'request_d6948721_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_rfp_request',
            ['request_d6948721_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\RFPBundle\Entity\Request',
            'request_86063709',
            'request_d6948721',
            RelationType::MANY_TO_ONE
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
