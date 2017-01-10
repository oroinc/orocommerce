<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_11;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateAnonymousUserRoleQuery extends ParametrizedMigrationQuery
{
    const IS_AUTHENTICATED_ANONYMOUSLY  = 'IS_AUTHENTICATED_ANONYMOUSLY';
    const ROLE_FRONTEND_ANONYMOUS  = 'ROLE_FRONTEND_ANONYMOUS';

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
        $sql = 'UPDATE oro_customer_user_role SET role = :role WHERE role = :old_role';
        $parameters = [
            'old_role' => self::IS_AUTHENTICATED_ANONYMOUSLY,
            'role' => self::ROLE_FRONTEND_ANONYMOUS
        ];
        $types = ['old_role' => Type::STRING, 'role' => Type::STRING];

        $this->logQuery($logger, $sql, $parameters, $types);

        if (!$dryRun) {
            $this->connection->executeUpdate($sql, $parameters, $types);
        }
    }
}
