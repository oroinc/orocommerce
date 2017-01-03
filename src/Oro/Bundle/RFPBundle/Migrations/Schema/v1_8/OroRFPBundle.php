<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\TranslationBundle\Migration\DeleteTranslationKeysQuery;

class OroRFPBundle implements Migration, ExtendExtensionAwareInterface
{
    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroRfpRequestTable($schema);
        $this->createOroRfpRequestAddNoteTable($schema);
        $this->addOroRfpRequestAddNoteForeignKeys($schema);
        $queries->addQuery(new DeleteTranslationKeysQuery('messages', $this->getTranslationKeysForRemove()));
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
     * Get unused translation keys
     *
     * @return array
     */
    protected function getTranslationKeysForRemove()
    {
        return [
            'oro.rfp.message.request_status_saved',
            'oro.rfp.message.request_status_restored',
            'oro.rfp.message.request_status_deleted',
            'oro.rfp.message.request_status_not_found',
            'oro.rfp.message.request_status_changed',
            'oro.rfp.message.request_status_not_deletable',
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
        ];
    }
}
