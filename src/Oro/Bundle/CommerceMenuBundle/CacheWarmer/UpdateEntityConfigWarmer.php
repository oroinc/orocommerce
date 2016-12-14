<?php

namespace Oro\Bundle\CommerceMenuBundle\CacheWarmer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;

class UpdateEntityConfigWarmer implements CacheWarmerInterface
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param LoggerInterface $logger
     */
    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger)
    {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        /** @var Connection $defaultConnection */
        $defaultConnection = $this->managerRegistry->getConnection('config');

        $tableExists = SafeDatabaseChecker::tablesExist(
            $defaultConnection,
            SafeDatabaseChecker::getTableName($this->managerRegistry, EntityConfigModel::class)
        );
        if (!$tableExists) {
            return;
        }

        $className = 'Oro\Bundle\MenuBundle\Entity\MenuItem';

        $this->executeQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config_field WHERE entity_id IN ('
                . 'SELECT id FROM oro_entity_config WHERE class_name = :class)',
                ['class' => $className],
                ['class' => 'string']
            ),
            $defaultConnection
        );

        $this->executeQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class',
                ['class' => $className],
                ['class' => 'string']
            ),
            $defaultConnection
        );
    }

    /**
     * @param ParametrizedMigrationQuery $query
     * @param Connection $connection
     */
    private function executeQuery(ParametrizedMigrationQuery $query, Connection $connection)
    {
        $query->setConnection($connection);
        $query->execute($this->logger);
    }
}
