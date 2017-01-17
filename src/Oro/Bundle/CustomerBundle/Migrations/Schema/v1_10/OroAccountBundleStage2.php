<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroAccountBundleStage2 implements
    Migration,
    RenameExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    OrderedMigrationInterface
{
    use MigrationConstraintTrait;

    /**
     * @var RenameExtension
     */
    private $renameExtension;

    /**
     * @var ActivityExtension
     **/
    private $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameCustomer($schema);
        $this->renameCustomerUser($schema);
        $this->renameAccountUserSidebarWidget($schema);
        $this->renameAccountUserSidebarState($schema);
        $this->renameCustomerSettings($schema);
        $this->renameAccountUserAddressToAddressType($schema);
        $this->renameAccountAddressToAddressType($schema);
        $this->renameAccountUserRole($schema);
        $this->renameCustomerGroup($schema);
        $this->renameAccWindowsState($schema);

        $this->activityExtension->addActivityAssociation(
            $schema,
            'oro_note',
            'oro_customer_group'
        );
        $this->activityExtension->addActivityAssociation(
            $schema,
            'oro_note',
            'oro_customer_user'
        );
        $this->activityExtension->addActivityAssociation(
            $schema,
            'oro_note',
            'oro_customer'
        );
        $this->alterScopes($schema);
    }

    /**
     * @param Schema $schema
     */
    private function renameAccountUserRole(Schema $schema)
    {
        $this->activityExtension->addActivityAssociation(
            $schema,
            'oro_note',
            'oro_customer_user_role'
        );
        $table = $schema->getTable("oro_cus_user_access_role");
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user_role'),
            ['customer_user_role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table = $schema->getTable("oro_customer_role_to_website");
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user_role'),
            ['customer_user_role_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        if ($schema->hasTable("oro_rel_6f8f552a9df6f4d81e2432") &&
            !$this->fkExists($schema->getTable("oro_rel_6f8f552a9df6f4d81e2432"), 'customeruserrole_id')
        ) {
            $table = $schema->getTable("oro_rel_6f8f552a9df6f4d81e2432");
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_customer_user_role'),
                ['customeruserrole_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );
        }

        $table = $schema->getTable("oro_rel_c3990ba6d7fa01cd30d950");
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user_role'),
            ['customeruserrole_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addIndex(['customeruserrole_id'], 'idx_7ef0304fd5b2dcce', []);
    }

    /**
     * @param Schema $schema
     */
    private function renameAccountUserSidebarWidget(Schema $schema)
    {
        $table = $schema->getTable("oro_customer_user_sdbar_wdg");

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE'],
            'fk_customer_user_id'
        );
        $table->addIndex(['position'], 'oro_cus_sdar_wdgs_pos_idx', []);
        $table->addIndex(['customer_user_id', 'placement'], 'oro_cus_sdbr_wdgs_usr_place_idx', []);
    }

    /**
     * @param Schema $schema
     */
    private function renameAccWindowsState(Schema $schema)
    {
        $table = $schema->getTable("oro_cus_windows_state");

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addIndex(['customer_user_id'], 'oro_cus_windows_state_acu_idx', []);
    }

    /**
     * @param Schema $schema
     */
    private function renameAccountUserSidebarState(Schema $schema)
    {
        $table = $schema->getTable('oro_customer_user_sdbar_st');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );

        $table->addUniqueIndex(['customer_user_id', 'position'], 'oro_cus_sdbar_st_unq_idx');
    }

    /**
     * @param Schema $schema
     */
    private function renameCustomerSettings(Schema $schema)
    {
        $table = $schema->getTable('oro_customer_user_settings');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addUniqueIndex(['customer_user_id', 'website_id'], 'unique_acc_user_website');
    }

    /**
     * @param Schema $schema
     */
    private function renameAccountUserAddressToAddressType(Schema $schema)
    {
        $table = $schema->getTable('oro_cus_usr_adr_to_adr_type');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user_address'),
            ['customer_user_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addUniqueIndex(['customer_user_address_id', 'type_name'], 'oro_customer_user_adr_id_type_name_idx');
    }

    /**
     * @param Schema $schema
     */
    private function renameAccountAddressToAddressType(Schema $schema)
    {
        $table = $schema->getTable('oro_customer_adr_adr_type');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_address'),
            ['customer_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addUniqueIndex(['customer_address_id', 'type_name'], 'oro_customer_adr_id_type_name_idx');
    }

    /**
     * @param Schema $schema
     */
    private function renameCustomerGroup(Schema $schema)
    {
        $table = $schema->getTable('oro_rel_c3990ba616cbf45899499b');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_group'),
            ['customergroup_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        if ($schema->hasTable('oro_rel_6f8f552a16cbf458eebf8a')) {
            $table = $schema->getTable('oro_rel_6f8f552a16cbf458eebf8a');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_customer_group'),
                ['customergroup_id'],
                ['id'],
                ['onUpdate' => null, 'onDelete' => 'CASCADE']
            );
        }
    }

    /**
     * @param Schema $schema
     */
    public function renameCustomer(Schema $schema)
    {
        $schema->getTable('oro_customer')->addIndex(['name'], 'oro_customer_name_idx', []);

        $schema->getTable('oro_customer_user')
            ->addForeignKeyConstraint(
                $schema->getTable('oro_customer'),
                ['customer_id'],
                ['id'],
                ['onDelete' => 'SET NULL', 'onUpdate' => null]
            );

        $schema->getTable('oro_attachment')
            ->addForeignKeyConstraint(
                $schema->getTable('oro_customer'),
                ['customer_e2cfcbe5_id'],
                ['id'],
                ['onDelete' => 'SET NULL', 'onUpdate' => null]
            );

        if ($schema->hasTable('oro_rel_c3990ba6784fec5f6e321b')) {
            $schema->getTable('oro_rel_c3990ba6784fec5f6e321b')
                ->addForeignKeyConstraint(
                    $schema->getTable('oro_customer'),
                    ['customer_id'],
                    ['id'],
                    ['onDelete' => 'CASCADE', 'onUpdate' => null]
                );
        }
        if ($schema->hasTable('oro_rel_6f8f552a784fec5fcd148c')) {
            $schema->getTable('oro_rel_6f8f552a784fec5fcd148c')
                ->addForeignKeyConstraint(
                    $schema->getTable('oro_customer'),
                    ['customer_id'],
                    ['id'],
                    ['onDelete' => 'CASCADE', 'onUpdate' => null]
                );
        }

        $table = $schema->getTable('oro_customer_user_role');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addUniqueIndex(['customer_id', 'label'], 'oro_customer_user_role_customer_id_label_idx');

        $table = $schema->getTable('oro_customer_sales_reps');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     * @param Schema $schema
     */
    public function renameCustomerUser(Schema $schema)
    {
        $table = $schema->getTable('oro_customer_user_org');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $schema->getTable('oro_audit')
            ->addForeignKeyConstraint(
                $schema->getTable('oro_customer_user'),
                ['customer_user_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null],
                'fk_oro_audit_account_user_id'
            );

        $schema->getTable('oro_cus_navigation_history')
            ->addForeignKeyConstraint(
                $schema->getTable('oro_customer_user'),
                ['customer_user_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );

        $schema->getTable('oro_customer_user_sales_reps')
            ->addForeignKeyConstraint(
                $schema->getTable('oro_customer_user'),
                ['customer_user_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );

        $schema->getTable('oro_cus_pagestate')
            ->addForeignKeyConstraint(
                $schema->getTable('oro_customer_user'),
                ['customer_user_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );

        $schema->getTable('oro_cus_navigation_item')
            ->addForeignKeyConstraint(
                $schema->getTable('oro_customer_user'),
                ['customer_user_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );

        $table = $schema->getTable('oro_cus_navigation_item');
        $table->addIndex(['customer_user_id', 'position'], 'oro_sorted_items_idx', []);

        if ($schema->hasTable('oro_rel_c3990ba63708e583a2c61e')) {
            $table = $schema->getTable('oro_rel_c3990ba63708e583a2c61e');
            $table->addForeignKeyConstraint(
                $schema->getTable('oro_customer_user'),
                ['customeruser_id'],
                ['id'],
                ['onDelete' => 'CASCADE', 'onUpdate' => null]
            );
        }

        $table = $schema->getTable('oro_rel_46a29d193708e583c5ba51');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customeruser_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table = $schema->getTable('oro_rel_265353703708e583c5ba51');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customeruser_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );

        $table = $schema->getTable('oro_attachment');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_539cf909_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        if ($schema->hasTable('oro_rel_46a29d19a6adb604aeb863') &&
            !$this->fkExists($schema->getTable("oro_rel_46a29d19a6adb604aeb863"), 'customeruser_id')
        ) {
            $schema->getTable('oro_rel_46a29d19a6adb604aeb863')
                ->addForeignKeyConstraint(
                    $schema->getTable('oro_customer_user'),
                    ['customeruser_id'],
                    ['id'],
                    ['onDelete' => 'SET NULL', 'onUpdate' => null]
                );
        }

        if ($schema->hasTable('oro_rel_c3990ba6a6adb604193652') &&
            !$this->fkExists($schema->getTable("oro_rel_c3990ba6a6adb604193652"), 'customeruser_id')
        ) {
            $schema->getTable('oro_rel_c3990ba6a6adb604193652')
                ->addForeignKeyConstraint(
                    $schema->getTable('oro_customer_user'),
                    ['customeruser_id'],
                    ['id'],
                    ['onDelete' => 'SET NULL', 'onUpdate' => null]
                );
        }
    }

    /**
     * @param Schema $schema
     */
    private function alterScopes(Schema $schema)
    {
        $table = $schema->getTable('oro_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_group'),
            ['customerGroup_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Sets the RenameExtension
     *
     * @param RenameExtension $renameExtension
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * Sets the ActivityExtension
     *
     * @param ActivityExtension $activityExtension
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * Get the order of this migration
     *
     * @return integer
     */
    public function getOrder()
    {
        return 2;
    }

    /**
     * @param Table $table
     * @param $columnName
     *
     * @return bool
     */
    protected function fkExists(Table $table, $columnName)
    {
        $foreignKeys = $table->getForeignKeys();
        foreach ($foreignKeys as $key) {
            if ($key->getLocalColumns() === [$columnName]) {
                return true;
            }
        }

        return false;
    }
}
