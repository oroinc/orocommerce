<?php

namespace Oro\Bundle\AccountBundle\Migrations\Schema\v1_7;

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

        // email to account user association
        $extension->renameTable($schema, $queries, 'oro_rel_26535370a6adb604a9b8e1', 'oro_rel_26535370a6adb604aeb863');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\EmailBundle\Entity\Email',
            'Oro\Bundle\AccountBundle\Entity\AccountUser',
            'account_user_489123cf',
            'account_user_795f990e',
            RelationType::MANY_TO_MANY
        ));

        // calendar event to account user association
        $extension->renameTable($schema, $queries, 'oro_rel_46a29d19a6adb604a9b8e1', 'oro_rel_46a29d19a6adb604aeb863');
        $queries->addQuery(new UpdateExtendRelationQuery(
            'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            'Oro\Bundle\AccountBundle\Entity\AccountUser',
            'account_user_489123cf',
            'account_user_795f990e',
            RelationType::MANY_TO_MANY
        ));

        $this->updateAttachments($schema, $queries);
        $this->updateNotes($schema, $queries);

        // entity tables
        $extension->renameTable($schema, $queries, 'orob2b_account', 'oro_account');
        $extension->renameTable($schema, $queries, 'orob2b_account_user', 'oro_account_user');
        $extension->renameTable($schema, $queries, 'orob2b_acc_user_access_role', 'oro_acc_user_access_role');
        $extension->renameTable($schema, $queries, 'orob2b_account_group', 'oro_account_group');
        $extension->renameTable($schema, $queries, 'orob2b_account_user_org', 'oro_account_user_org');
        $extension->renameTable($schema, $queries, 'orob2b_account_user_role', 'oro_account_user_role');
        $extension->renameTable($schema, $queries, 'orob2b_account_role_to_website', 'oro_account_role_to_website');
        $extension->renameTable($schema, $queries, 'orob2b_account_user_settings', 'oro_account_user_settings');

        $extension->renameTable($schema, $queries, 'orob2b_acc_navigation_history', 'oro_acc_navigation_history');
        $extension->renameTable($schema, $queries, 'orob2b_acc_navigation_item', 'oro_acc_navigation_item');
        $extension->renameTable($schema, $queries, 'orob2b_acc_nav_item_pinbar', 'oro_acc_nav_item_pinbar');
        $extension->renameTable($schema, $queries, 'orob2b_account_user_sdbar_st', 'oro_account_user_sdbar_st');
        $extension->renameTable($schema, $queries, 'orob2b_account_user_sdbar_wdg', 'oro_account_user_sdbar_wdg');
        $extension->renameTable($schema, $queries, 'orob2b_acc_pagestate', 'oro_acc_pagestate');
        $extension->renameTable($schema, $queries, 'orob2b_windows_state', 'oro_account_windows_state');

        $extension->renameTable($schema, $queries, 'orob2b_account_address', 'oro_account_address');
        $extension->renameTable($schema, $queries, 'orob2b_account_adr_adr_type', 'oro_account_adr_adr_type');
        $extension->renameTable($schema, $queries, 'orob2b_account_user_address', 'oro_account_user_address');
        $extension->renameTable($schema, $queries, 'orob2b_acc_usr_adr_to_adr_type', 'oro_acc_usr_adr_to_adr_type');

        $extension->renameTable($schema, $queries, 'orob2b_category_visibility', 'oro_category_visibility');
        $extension->renameTable($schema, $queries, 'orob2b_acc_category_visibility', 'oro_acc_category_visibility');
        $extension->renameTable($schema, $queries, 'orob2b_acc_grp_ctgr_visibility', 'oro_acc_grp_ctgr_visibility');
        $extension->renameTable($schema, $queries, 'orob2b_product_visibility', 'oro_product_visibility');
        $extension->renameTable($schema, $queries, 'orob2b_acc_product_visibility', 'oro_acc_product_visibility');
        $extension->renameTable($schema, $queries, 'orob2b_acc_grp_prod_visibility', 'oro_acc_grp_prod_visibility');

        $extension->renameTable($schema, $queries, 'orob2b_prod_vsb_resolv', 'oro_prod_vsb_resolv');
        $extension->renameTable($schema, $queries, 'orob2b_acc_grp_prod_vsb_resolv', 'oro_acc_grp_prod_vsb_resolv');
        $extension->renameTable($schema, $queries, 'orob2b_acc_prod_vsb_resolv', 'oro_acc_prod_vsb_resolv');
        $extension->renameTable($schema, $queries, 'orob2b_ctgr_vsb_resolv', 'oro_ctgr_vsb_resolv');
        $extension->renameTable($schema, $queries, 'orob2b_acc_grp_ctgr_vsb_resolv', 'oro_acc_grp_ctgr_vsb_resolv');
        $extension->renameTable($schema, $queries, 'orob2b_acc_ctgr_vsb_resolv', 'oro_acc_ctgr_vsb_resolv');

        $extension->renameTable($schema, $queries, 'orob2b_account_sales_reps', 'oro_account_sales_reps');
        $extension->renameTable($schema, $queries, 'orob2b_account_user_sales_reps', 'oro_account_user_sales_reps');

        // indexes
        $schema->getTable('orob2b_account')->dropIndex('orob2b_account_name_idx');
        $schema->getTable('orob2b_account_group')->dropIndex('orob2b_account_group_name_idx');
        $schema->getTable('orob2b_account_user_role')->dropIndex('orob2b_account_user_role_account_id_label_idx');

        $extension->addIndex($schema, $queries, 'oro_account', ['name'], 'oro_account_name_idx');
        $extension->addIndex($schema, $queries, 'oro_account_group', ['name'], 'oro_account_group_name_idx');
        $extension->addUniqueIndex(
            $schema,
            $queries,
            'oro_account_group',
            ['account_id', 'label'],
            'oro_account_user_role_account_id_label_idx'
        );
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
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\AccountBundle\Entity\Account',
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
            'Oro\Bundle\AttachmentBundle\Entity\Attachment',
            'Oro\Bundle\AccountBundle\Entity\AccountUser',
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
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\AccountBundle\Entity\AccountUser',
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
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\AccountBundle\Entity\AccountUserRole',
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
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\AccountBundle\Entity\Account',
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
            'Oro\Bundle\NoteBundle\Entity\Note',
            'Oro\Bundle\AccountBundle\Entity\AccountGroup',
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
