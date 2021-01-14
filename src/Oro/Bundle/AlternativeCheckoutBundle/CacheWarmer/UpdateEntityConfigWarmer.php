<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\CacheWarmer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Remove entity config for AlternativeCheckout entity.
 */
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

        if (!SafeDatabaseChecker::tablesExist($defaultConnection, 'oro_entity_config')) {
            return;
        }

        $query = new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_entity_config WHERE class_name = :class_name',
            ['class_name'  => 'Oro\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout'],
            ['class_name'  => Types::STRING]
        );

        $query->setConnection($defaultConnection);
        $query->execute($this->logger);
    }
}
