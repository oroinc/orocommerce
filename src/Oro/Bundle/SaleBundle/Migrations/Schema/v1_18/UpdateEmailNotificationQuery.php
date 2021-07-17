<?php

namespace Oro\Bundle\SaleBundle\Migrations\Schema\v1_18;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Change email notification in Backoffice Quote Flow with Approvals on create_new_quote_transition
 */
class UpdateEmailNotificationQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Update settings for notification rules for Quotes.');

        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * {@inheritdoc}
     */
    private function doExecute(LoggerInterface $logger, $dryRun = false): void
    {
        $query = 'UPDATE oro_notification_email_notif 
                  SET workflow_transition_name = :newTransitionName 
                  WHERE entity_name = :entityName and 
                  workflow_definition_name = :workflowDefinitionName and
                  workflow_transition_name = :transitionName and 
                  template_id = :templateId';
        $types = [
            'templateId' => Types::INTEGER,
            'newTransitionName' => Types::STRING,
            'entityName' => Types::STRING,
            'workflowDefinitionName' => Types::STRING,
            'transitionName' => Types::STRING,
        ];

        $params = [
            'templateId' => $this->getTemplateId(),
            'newTransitionName' => '__start__',
            'entityName' => 'Oro\\Bundle\\SaleBundle\\Entity\\Quote',
            'workflowDefinitionName' => 'b2b_quote_backoffice_approvals',
            'transitionName' => 'create_new_quote_transition',
        ];

        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($query, $params, $types);
        }
    }

    /**
     * Get quote_created template id
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getTemplateId(): int
    {
        return $this->connection->fetchColumn(
            'SELECT id FROM oro_email_template WHERE name = :name',
            [
                'name' => 'quote_created'
            ]
        );
    }
}
