<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class RenameTablesAndColumns implements Migration, RenameExtensionAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // email to order association
        $extension->renameTable($schema, $queries, 'oro_rel_2653537034e8bc9c23a92e', 'oro_rel_2653537034e8bc9c2ddbe0');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\EmailBundle\Entity\Email',
            'OroB2B\Bundle\OrderBundle\Entity\Order',
            'order_19226b65',
            'order_5726bf8f',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to order association
        $extension->renameTable($schema, $queries, 'oro_rel_46a29d1934e8bc9c23a92e', 'oro_rel_46a29d1934e8bc9c2ddbe0');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\CalendarBundle\Entity\CalendarEvent',
            'OroB2B\Bundle\OrderBundle\Entity\Order',
            'order_19226b65',
            'order_5726bf8f',
            RelationType::MANY_TO_MANY
        ));

        // attachments
        $attachments = $schema->getTable('oro_attachment');
        $extension->renameColumn($schema, $queries, $attachments, 'order_f0cd67_id', 'order_50627d4f_id');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\AttachmentBundle\Entity\Attachment',
            'OroB2B\Bundle\OrderBundle\Entity\Order',
            'order_f0cd67',
            'order_50627d4f',
            RelationType::MANY_TO_ONE
        ));

        // notes
        $notes = $schema->getTable('oro_note');
        $extension->renameColumn($schema, $queries, $notes, 'order_f0cd67_id', 'order_50627d4f_id');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\NoteBundle\Entity\Note',
            'OroB2B\Bundle\OrderBundle\Entity\Order',
            'order_f0cd67',
            'order_50627d4f',
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
