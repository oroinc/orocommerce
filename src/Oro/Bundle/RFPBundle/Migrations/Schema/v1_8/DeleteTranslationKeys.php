<?php

namespace Oro\Bundle\RFPBundle\Migrations\Schema\v1_8;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Connection;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class DeleteTranslationKeys extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Remove unused translation keys for RFQ status field';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->deleteTranslationKeys($logger);
    }

    /**
     * @param LoggerInterface $logger
     */
    protected function deleteTranslationKeys(LoggerInterface $logger)
    {
        // Delete unused translation keys.
        $params = [
            'translation_keys'   => [
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
            ]
        ];

        $types  = [
            'translation_keys'   => Connection::PARAM_STR_ARRAY
        ];

        $sql = "DELETE FROM oro_translation_key" .
            " WHERE key IN (:translation_keys)" .
            " AND domain = 'messages' ";

        $this->logQuery($logger, $sql, $params, $types);
        $this->connection->executeUpdate($sql, $params, $types);
    }
}
