<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ConfigBundle\Migration\RenameConfigSectionQuery;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class OroRFPBundle implements Migration, RenameExtensionAwareInterface
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

        // rename tables
        $extension->renameTable($schema, $queries, 'orob2b_rfp_request', 'oro_rfp_request');
        $extension->renameTable($schema, $queries, 'orob2b_rfp_status', 'oro_rfp_status');
        $extension->renameTable($schema, $queries, 'orob2b_rfp_status_translation', 'oro_rfp_status_translation');
        $extension->renameTable($schema, $queries, 'orob2b_rfp_request_product', 'oro_rfp_request_product');
        $extension->renameTable($schema, $queries, 'orob2b_rfp_request_prod_item', 'oro_rfp_request_prod_item');

        // rename indexes
        $schema->getTable('orob2b_rfp_status')->dropIndex('orob2b_rfp_status_name_idx');
        $schema->getTable('orob2b_rfp_status_translation')->dropIndex('orob2b_rfp_status_trans_idx');

        $extension->addIndex($schema, $queries, 'oro_rfp_status', ['name'], 'oro_rfp_status_name_idx');
        $extension->addIndex(
            $schema,
            $queries,
            'oro_rfp_status_translation',
            ['locale', 'object_id', 'field'],
            'oro_rfp_status_trans_idx'
        );

        // system configuration
        $queries->addPostQuery(new RenameConfigSectionQuery('oro_b2b_rfp', 'oro_rfp'));
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
