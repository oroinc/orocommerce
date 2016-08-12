<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Schema\v1_7;

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
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // email to account user association
        $extension->renameTable($schema, $queries, 'oro_rel_26535370a6adb604a9b8e1', 'oro_rel_26535370a6adb604aeb863');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\EmailBundle\Entity\Email',
            'OroB2B\Bundle\AccountBundle\Entity\AccountUser',
            'account_user_489123cf',
            'account_user_795f990e',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to account user association
        $extension->renameTable($schema, $queries, 'oro_rel_46a29d19a6adb604a9b8e1', 'oro_rel_46a29d19a6adb604aeb863');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\CalendarBundle\Entity\CalendarEvent',
            'OroB2B\Bundle\AccountBundle\Entity\AccountUser',
            'account_user_489123cf',
            'account_user_795f990e',
            RelationType::MANY_TO_MANY
        ));

        $this->updateAttachments($schema, $queries);
        $this->updateNotes($schema, $queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function updateAttachments(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;
        $attachments = $schema->getTable('oro_attachment');

        $attachments->removeForeignKey('FK_FA0FE081B3C3AB7');
        $extension->renameColumn($schema, $queries, $attachments, 'account_557018f_id', 'account_8d93c122_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_attachment',
            'orob2b_account',
            ['account_8d93c122_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\AttachmentBundle\Entity\Attachment',
            'OroB2B\Bundle\AccountBundle\Entity\Account',
            'account_557018f',
            'account_8d93c122',
            RelationType::MANY_TO_ONE
        ));

        $attachments->removeForeignKey('FK_FA0FE081E7106C4F');
        $extension->renameColumn(
            $schema,
            $queries,
            $attachments,
            'account_user_1cc98a31_id',
            'account_user_7e92c4f1_id'
        );
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_attachment',
            'orob2b_account_user',
            ['account_user_7e92c4f1_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\AttachmentBundle\Entity\Attachment',
            'OroB2B\Bundle\AccountBundle\Entity\AccountUser',
            'account_user_1cc98a31',
            'account_user_7e92c4f1',
            RelationType::MANY_TO_ONE
        ));
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function updateNotes(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;
        $notes = $schema->getTable('oro_note');

        $notes->removeForeignKey('FK_BA066CE1E7106C4F');
        $extension->renameColumn($schema, $queries, $notes, 'account_user_1cc98a31_id', 'account_user_7e92c4f1_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_account_user',
            ['account_user_7e92c4f1_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\NoteBundle\Entity\Note',
            'OroB2B\Bundle\AccountBundle\Entity\AccountUser',
            'account_user_1cc98a31',
            'account_user_7e92c4f1',
            RelationType::MANY_TO_ONE
        ));

        $notes->removeForeignKey('FK_BA066CE16E157C94');
        $extension->renameColumn(
            $schema,
            $queries,
            $notes,
            'account_user_role_5d57148e_id',
            'account_user_role_abeddea9_id'
        );
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_account_user_role',
            ['account_user_role_abeddea9_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\NoteBundle\Entity\Note',
            'OroB2B\Bundle\AccountBundle\Entity\AccountUserRole',
            'account_user_role_5d57148e',
            'account_user_role_abeddea9',
            RelationType::MANY_TO_ONE
        ));

        $notes->removeForeignKey('fk_oro_note_account_557018f_id');
        $extension->renameColumn($schema, $queries, $notes, 'account_557018f_id', 'account_8d93c122_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_account',
            ['account_8d93c122_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'fk_oro_note_account_8d93c122_id'
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\NoteBundle\Entity\Note',
            'OroB2B\Bundle\AccountBundle\Entity\Account',
            'account_557018f',
            'account_8d93c122',
            RelationType::MANY_TO_ONE
        ));

        $notes->removeForeignKey('FK_BA066CE1E6FAD316');
        $extension->renameColumn($schema, $queries, $notes, 'account_group_338fe797_id', 'account_group_a8897e69_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'orob2b_account_group',
            ['account_group_a8897e69_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'OroB2B\Bundle\NoteBundle\Entity\Note',
            'OroB2B\Bundle\AccountBundle\Entity\AccountGroup',
            'account_group_338fe797',
            'account_group_a8897e69',
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
