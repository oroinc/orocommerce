<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_5;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

class SetShippingMethodsConfigsRuleOrganizationQuery extends ParametrizedSqlMigrationQuery
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     *
     * @throws \Exception
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $this->setDefaultOrganization($logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     *
     * @throws \Exception
     */
    private function setDefaultOrganization(LoggerInterface $logger, $dryRun)
    {
        $sql = '
            SELECT id
            FROM  oro_organization
            ORDER BY id
            LIMIT 1
        ';
        $this->logQuery($logger, $sql);

        $organizationId = $this->connection->query($sql)->fetchColumn(0);
        if (!$organizationId) {
            throw new \Exception('No organizations found in system');
        }

        $sql = '
            UPDATE oro_ship_method_configs_rule
            SET organization_id = :organization_id
        ';
        $params = ['organization_id' => $organizationId];
        $types = ['organization_id' => \PDO::PARAM_INT];
        $this->logQuery($logger, $sql, $params, $types);

        if (!$dryRun) {
            $this->connection->executeStatement($sql, $params, $types);
        }
    }
}
