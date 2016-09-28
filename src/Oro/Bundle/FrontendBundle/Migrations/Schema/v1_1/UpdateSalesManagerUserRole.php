<?php

namespace Oro\Bundle\FrontendBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateSalesManagerUserRole extends ParametrizedMigrationQuery
{
    const OLD_ACCOUNT_NAME  = 'ROLE_SALES_MANAGER';
    const NEW_ACCOUNT_NAME  = 'ROLE_SALES_ASSISTANT';
    const NEW_ACCOUNT_LABEL = 'Sales Assistant';

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
        $query = [
            'Update oro_access_role set role = :role, label = :label Where role = :old_role',
            [
                'old_role' => self::OLD_ACCOUNT_NAME,
                'role' => self::NEW_ACCOUNT_NAME,
                'label' => self::NEW_ACCOUNT_LABEL
            ],
            ['old_role' => Type::STRING, 'role' => Type::STRING, 'label' => Type::STRING]
        ];

        $this->logQuery($logger, $query[0], $query[1], $query[2]);
        if (!$dryRun) {
            $this->connection->executeUpdate($query[0], $query[1], $query[2]);
        }
    }
}
