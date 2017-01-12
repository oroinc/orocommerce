<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveTableQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\TranslationBundle\Migration\DeleteTranslationKeysQuery;

class OroRFPBundle implements
    Migration,
    RenameExtensionAwareInterface,
    OrderedMigrationInterface,
    ExtendExtensionAwareInterface
{
    use MigrationConstraintTrait;

    /** @var RenameExtension */
    private $renameExtension;

    /** @var ExtendExtension */
    protected $extendExtension;

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
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * Get the order of this migration
     *
     * @return integer
     */
    public function getOrder()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroRfpRequestTable($schema);
        $this->createOroRfpRequestAddNoteTable($schema);
        $this->addOroRfpRequestAddNoteForeignKeys($schema);
        $this->dropRfpStatus($schema);
        $this->dropRfpStatusTranslation($schema);

        $this->updateEntityConfigs($queries);

        foreach ($this->getTranslationKeysForRemove() as $domain => $keys) {
            $queries->addQuery(new DeleteTranslationKeysQuery($domain, $keys));
        }

        $this->renameAccountToCustomer($schema, $queries);
    }

    /**
     * Update oro_rfp_request table
     *
     * @param Schema $schema
     */
    protected function updateOroRfpRequestTable(Schema $schema)
    {
        $this->extendExtension->addEnumField(
            $schema,
            'oro_rfp_request',
            'customer_status',
            'rfp_customer_status',
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );

        $this->extendExtension->addEnumField(
            $schema,
            'oro_rfp_request',
            'internal_status',
            'rfp_internal_status',
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );

        $table = $schema->getTable('oro_rfp_request');
        $table->dropColumn('status_id');
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function renameAccountToCustomer(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_rfp_request');
        $table->removeForeignKey($this->getConstraintName($table, 'account_user_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_user_id',
            'customer_user_id'
        );
        $table->removeForeignKey($this->getConstraintName($table, 'account_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_id',
            'customer_id'
        );

        $table = $schema->getTable('oro_rfp_assigned_acc_users');
        $table->removeForeignKey($this->getConstraintName($table, 'account_user_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'account_user_id',
            'customer_user_id'
        );
        $this->renameExtension->renameTable(
            $schema,
            $queries,
            'oro_rfp_assigned_acc_users',
            'oro_rfp_assigned_cus_users'
        );
    }

    /**
     * @param Schema $schema
     */
    protected function createOroRfpRequestAddNoteTable(Schema $schema)
    {
        $table = $schema->createTable('oro_rfp_request_add_note');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('request_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 100]);
        $table->addColumn('author', 'string', ['length' => 100]);
        $table->addColumn('user_id', 'integer', []);
        $table->addColumn('text', 'text', []);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroRfpRequestAddNoteForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_rfp_request_add_note');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_rfp_request'),
            ['request_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function dropRfpStatus(Schema $schema)
    {
        $schema->dropTable('oro_rfp_status');
    }

    /**
     * @param Schema $schema
     */
    protected function dropRfpStatusTranslation(Schema $schema)
    {
        $schema->dropTable('oro_rfp_status_translation');
    }

    /**
     * @param QueryBag $queries
     */
    protected function updateEntityConfigs(QueryBag $queries)
    {
        $queries->addPostQuery(new RemoveFieldQuery('Oro\Bundle\RFPBundle\Entity\Request', 'status'));
        $queries->addPostQuery(new RemoveTableQuery('Oro\Bundle\RFPBundle\Entity\RequestStatus'));
    }

    /**
     * Get unused translation keys
     *
     * @return array
     */
    protected function getTranslationKeysForRemove()
    {
        return [
            'messages' => [
                'oro.rfp.menu.request_status_list.description',
                'oro.rfp.menu.shortcut_request_status_list.description',
                'oro.rfp.message.request_status_saved',
                'oro.rfp.message.request_status_restored',
                'oro.rfp.message.request_status_deleted',
                'oro.rfp.message.request_status_not_found',
                'oro.rfp.message.request_status_changed',
                'oro.rfp.message.request_status_not_deletable',
                'oro.rfp.request.status.label',
                'oro.rfp.requeststatus.entity_label',
                'oro.rfp.requeststatus.entity_plural_label',
                'oro.rfp.requeststatus.id.label',
                'oro.rfp.requeststatus.name.label',
                'oro.rfp.requeststatus.label.label',
                'oro.rfp.requeststatus.sort_order.label',
                'oro.rfp.requeststatus.deleted.label',
                'oro.rfp.requeststatus.translations.label',
                'oro.rfp.system_configuration.groups.requeststatus.title',
                'oro.rfp.system_configuration.fields.requeststatus_default.title',
                'oro.rfp.btn.change_status',
                'oro.rfp.widget.change_status_title',
                'oro.frontend.rfp.request.status.label',
            ],
            'entities' => [
                'request_status.open',
                'request_status.closed',
                'request_status.draft',
                'request_status.canceled',
                'request_status.deleted',
            ],
        ];
    }
}
