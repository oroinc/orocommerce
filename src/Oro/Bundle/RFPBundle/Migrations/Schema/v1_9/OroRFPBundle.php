<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveFieldQuery;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveTableQuery;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RFPBundle\Migrations\Data\ORM\LoadRequestCustomerStatuses;
use Oro\Bundle\RFPBundle\Migrations\Data\ORM\LoadRequestInternalStatuses;
use Oro\Bundle\TranslationBundle\Migration\DeleteTranslationKeysQuery;

class OroRFPBundle implements Migration, ExtendExtensionAwareInterface, RenameExtensionAwareInterface
{
    use MigrationConstraintTrait;

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var RenameExtension */
    private $renameExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * Sets the RenameExtension
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroRfpRequestAddNoteTable($schema);
        $this->addOroRfpRequestAddNoteForeignKeys($schema);

        $this->updateOroRfpRequestTable($schema);
        $this->removeOroRfpRequestStatusTable($schema, $queries);
        $this->removeUnusedTranslationKeys($queries);
        $this->renameFieldForAssignedCustomers($schema, $queries);
        $this->renameFieldForAssignedUsers($schema, $queries);
    }

    /**
     * Update oro_rfp_request table
     */
    protected function updateOroRfpRequestTable(Schema $schema)
    {
        $table = $schema->getTable('oro_rfp_request');
        $table->dropColumn('status_id');

        $customerStatusOptions = new OroOptions();
        $customerStatusOptions->set(
            'enum',
            'immutable_codes',
            LoadRequestCustomerStatuses::getDataKeys()
        );

        $customerStatusEnumTable = $this->extendExtension->addEnumField(
            $schema,
            'oro_rfp_request',
            'customer_status',
            'rfp_customer_status',
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );
        $customerStatusEnumTable->addOption(OroOptions::KEY, $customerStatusOptions);

        $internalStatusOptions = new OroOptions();
        $internalStatusOptions->set(
            'enum',
            'immutable_codes',
            LoadRequestInternalStatuses::getDataKeys()
        );

        $internalStatusEnumTable = $this->extendExtension->addEnumField(
            $schema,
            'oro_rfp_request',
            'internal_status',
            'rfp_internal_status',
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );

        $internalStatusEnumTable->addOption(OroOptions::KEY, $internalStatusOptions);
    }

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

    protected function removeOroRfpRequestStatusTable(Schema $schema, QueryBag $queries)
    {
        $schema->dropTable('oro_rfp_status');
        $schema->dropTable('oro_rfp_status_translation');

        $queries->addPostQuery(new RemoveFieldQuery('Oro\Bundle\RFPBundle\Entity\Request', 'status'));
        $queries->addPostQuery(new RemoveTableQuery('Oro\Bundle\RFPBundle\Entity\RequestStatus'));
    }

    protected function removeUnusedTranslationKeys(QueryBag $queries)
    {
        $data = [
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
            ]
        ];

        foreach ($data as $domain => $keys) {
            $queries->addQuery(new DeleteTranslationKeysQuery($domain, $keys));
        }
    }

    protected function renameFieldForAssignedCustomers(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_rfp_assigned_cus_users');
        $table->removeForeignKey($this->getConstraintName($table, 'quote_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'quote_id',
            'request_id'
        );

        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_rfp_assigned_cus_users',
            'oro_rfp_request',
            ['request_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    protected function renameFieldForAssignedUsers(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_rfp_assigned_users');
        $table->removeForeignKey($this->getConstraintName($table, 'quote_id'));
        $this->renameExtension->renameColumn(
            $schema,
            $queries,
            $table,
            'quote_id',
            'request_id'
        );

        $this->renameExtension->addForeignKeyConstraint(
            $schema,
            $queries,
            'oro_rfp_assigned_users',
            'oro_rfp_request',
            ['request_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
