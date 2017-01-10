<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class AddFrontendAnonymousUserRoleQuery extends ParametrizedMigrationQuery
{
    const ROLE_NAME  = 'ROLE_FRONTEND_ANONYMOUS';

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
        $sql = 'INSERT INTO acl_security_identities (identifier, username) VALUES (:role, false)';
        $parameters = ['role' => self::ROLE_NAME];
        $types = ['role' => Type::STRING];

        $this->logQuery($logger, $sql, $parameters, $types);

        if (!$dryRun) {
            $this->connection->executeQuery($sql, $parameters, $types);
        }
    }
}
