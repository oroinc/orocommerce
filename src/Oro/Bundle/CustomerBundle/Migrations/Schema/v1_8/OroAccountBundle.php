<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\InstallerBundle\Migration\UpdateTableFieldQuery;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\FrontendBundle\Migration\UpdateExtendRelationQuery;

class OroAccountBundle implements Migration, RenameExtensionAwareInterface
{
    use MigrationConstraintTrait;

    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameActivityTables($schema, $queries);
        $this->updateAttachments($schema, $queries);
        $this->updateNotes($schema, $queries);
        $this->updateTableField($queries);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function renameActivityTables(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;

        // email to account user association
        $extension->renameTable($schema, $queries, 'oro_rel_26535370a6adb604aeb863', 'oro_rel_26535370a6adb604264ef1');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\CustomerBundle\Entity\AccountUser',
            'account_user_795f990e',
            'account_user_741cdecd',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to account user association
        $extension->renameTable($schema, $queries, 'oro_rel_46a29d19a6adb604aeb863', 'oro_rel_46a29d19a6adb604264ef1');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\CustomerBundle\Entity\AccountUser',
            'account_user_795f990e',
            'account_user_741cdecd',
            RelationType::MANY_TO_MANY
        ));
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    private function updateAttachments(Schema $schema, QueryBag $queries)
    {
        $extension = $this->renameExtension;
        $attachments = $schema->getTable('oro_attachment');

        $attachments->removeForeignKey('FK_FA0FE081140E2435');
        $extension->renameColumn(
            $schema,
            $queries,
            $attachments,
            'account_8d93c122_id',
            'account_8d1f63b9_id'
        );
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_attachment',
            'oro_account',
            ['account_8d1f63b9_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\CustomerBundle\Entity\Account',
            'account_8d93c122',
            'account_8d1f63b9',
            RelationType::MANY_TO_ONE
        ));

        $attachments->removeForeignKey('FK_FA0FE08135CF6547');
        $extension->renameColumn(
            $schema,
            $queries,
            $attachments,
            'account_user_7e92c4f1_id',
            'account_user_5919fc1d_id'
        );
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_attachment',
            'oro_account_user',
            ['account_user_5919fc1d_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\CustomerBundle\Entity\AccountUser',
            'account_user_7e92c4f1',
            'account_user_5919fc1d',
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

        $notes->removeForeignKey('FK_BA066CE135CF6547');
        $extension->renameColumn($schema, $queries, $notes, 'account_user_7e92c4f1_id', 'account_user_5919fc1d_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'oro_account_user',
            ['account_user_5919fc1d_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\CustomerBundle\Entity\AccountUser',
            'account_user_7e92c4f1',
            'account_user_5919fc1d',
            RelationType::MANY_TO_ONE
        ));

        $notes->removeForeignKey('FK_BA066CE17017C4');
        $extension->renameColumn(
            $schema,
            $queries,
            $notes,
            'account_user_role_abeddea9_id',
            'account_user_role_604160ea_id'
        );
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'oro_account_user_role',
            ['account_user_role_604160ea_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\CustomerBundle\Entity\AccountUserRole',
            'account_user_role_abeddea9',
            'account_user_role_604160ea',
            RelationType::MANY_TO_ONE
        ));

        $notes->removeForeignKey('fk_oro_note_account_8d93c122_id');
        $extension->renameColumn($schema, $queries, $notes, 'account_8d93c122_id', 'account_8d1f63b9_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'oro_account',
            ['account_8d1f63b9_id'],
            ['id'],
            ['onDelete' => 'SET NULL'],
            'fk_oro_note_account_8d1f63b9_id'
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\CustomerBundle\Entity\Account',
            'account_8d93c122',
            'account_8d1f63b9',
            RelationType::MANY_TO_ONE
        ));

        $notes->removeForeignKey('FK_BA066CE1254D1CEE');
        $extension->renameColumn($schema, $queries, $notes, 'account_group_a8897e69_id', 'account_group_1125b02_id');
        $extension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_note',
            'oro_account_group',
            ['account_group_1125b02_id'],
            ['id'],
            ['onDelete' => 'SET NULL']
        );
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\CustomerBundle\Entity\AccountGroup',
            'account_group_a8897e69',
            'account_group_1125b02',
            RelationType::MANY_TO_ONE
        ));
    }

    /**
     * @param QueryBag $queries
     */
    private function updateTableField(QueryBag $queries)
    {
        $queries->addQuery(new UpdateTableFieldQuery(
            'oro_migrations_data',
            'class_name',
            'AccountBundle',
            'CustomerBundle'
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
