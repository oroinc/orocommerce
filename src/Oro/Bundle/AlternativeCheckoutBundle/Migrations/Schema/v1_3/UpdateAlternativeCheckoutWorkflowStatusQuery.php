<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateAlternativeCheckoutWorkflowStatusQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
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
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        if ($this->isAccountExists($logger, 4)) {
            return;
        }

        $sql = 'UPDATE oro_workflow_definition SET active = :active WHERE name = :name';
        $params = ['active' => false, 'name' => 'b2b_flow_alternative_checkout'];
        $types  = ['active' => Type::BOOLEAN, 'name' => Type::STRING];

        $this->logQuery($logger, $sql, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($sql, $params, $types);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param int $accountId
     * @return bool
     */
    protected function isAccountExists(LoggerInterface $logger, $accountId)
    {
        $sql = 'SELECT a.id FROM oro_customer AS a WHERE a.id = :id LIMIT 1';
        $params = ['id' => $accountId];
        $types  = ['id' => Type::INTEGER];

        $this->logQuery($logger, $sql, $params, $types);

        $rows = $this->connection->fetchAll($sql, $params, $types);

        return $rows ? true : false;
    }
}
