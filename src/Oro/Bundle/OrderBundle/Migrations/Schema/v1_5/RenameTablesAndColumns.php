<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema\v1_5;

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

        // email to order association
        $extension->renameTable($schema, $queries, 'oro_rel_2653537034e8bc9c23a92e', 'oro_rel_2653537034e8bc9c2ddbe0');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\OrderBundle\Entity\Order',
            'order_19226b65',
            'order_5726bf8f',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to order association
        $extension->renameTable($schema, $queries, 'oro_rel_46a29d1934e8bc9c23a92e', 'oro_rel_46a29d1934e8bc9c2ddbe0');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\OrderBundle\Entity\Order',
            'order_19226b65',
            'order_5726bf8f',
            RelationType::MANY_TO_MANY
        ));

        // attachments
        $attachments = $schema->getTable('oro_attachment');

        $attachments->removeForeignKey('FK_FA0FE08139C4E2D');
        $extension->renameColumn($schema, $queries, $attachments, 'order_f0cd67_id', 'order_50627d4f_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_attachment',
            'orob2b_order',
            ['order_50627d4f_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\OrderBundle\Entity\Order',
            'order_f0cd67',
            'order_50627d4f',
            RelationType::MANY_TO_ONE
        ));

        // notes
        $notes = $schema->getTable('oro_note');

        $notes->removeForeignKey('fk_oro_note_order_f0cd67_id');
        $extension->renameColumn($schema, $queries, $notes, 'order_f0cd67_id', 'order_50627d4f_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_order',
            ['order_50627d4f_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'fk_oro_note_order_50627d4f_id'
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\OrderBundle\Entity\Order',
            'order_f0cd67',
            'order_50627d4f',
            RelationType::MANY_TO_ONE
        ));

        // entity tables
        $extension->renameTable($schema, $queries, 'orob2b_order', 'oro_order');
        $extension->renameTable($schema, $queries, 'orob2b_order_address', 'oro_order_address');
        $extension->renameTable($schema, $queries, 'orob2b_order_discount', 'oro_order_discount');
        $extension->renameTable($schema, $queries, 'orob2b_order_line_item', 'oro_order_line_item');

        // indexes
        $schema->getTable('orob2b_order')->dropIndex('orob2b_order_created_at_index');
        $extension->addIndex($schema, $queries, 'oro_order', ['created_at'], 'oro_order_created_at_index');
    }

    /**
     * {@inheritdoc}
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }
}
