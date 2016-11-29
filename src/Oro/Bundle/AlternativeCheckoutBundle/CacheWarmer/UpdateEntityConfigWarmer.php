<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\CacheWarmer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

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
        $defaultConnection = $this->managerRegistry->getConnection();

        $query = new ParametrizedSqlMigrationQuery(
            'DELETE FROM oro_entity_config WHERE class_name = :class_name',
            [
                'class_name'  => 'Oro\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout',
            ],
            [
                'class_name'  => Type::STRING
            ]
        );

        $query->setConnection($defaultConnection);
        $query->execute($this->logger);
    }
}
