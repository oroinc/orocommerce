<?php
declare(strict_types=1);

namespace Oro\Bundle\CommerceBundle\CacheWarmer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\SafeDatabaseChecker;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Ensures that extend entity cache can be built after entity removals and renaming.
 */
class ExtendEntityCacheWarmer implements CacheWarmerInterface
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $applicationInstalled;

    public function __construct(ManagerRegistry $managerRegistry, LoggerInterface $logger, $applicationInstalled)
    {
        $this->managerRegistry = $managerRegistry;
        $this->logger = $logger;
        $this->applicationInstalled = (bool)$applicationInstalled;
    }

    /**
     * Returns an array of class names of the deleted entities.
     *
     * For example:
     * ```
     * return [
     *     'Oro\Bundle\SomeBundle\Entity\Something',
     *     'Oro\Bundle\SomeBundle\Entity\SomethingElse',
     * ];
     * ```
     *
     * @return string[]
     */
    protected function getClassNamesOfDeletedEntities(): array
    {
        return [
            'Oro\Bundle\InvoiceBundle\Entity\Invoice',
            'Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem',
        ];
    }

    public function isOptional()
    {
        return false;
    }

    public function warmUp($cacheDir)
    {
        if (!$this->applicationInstalled) {
            return;
        }

        /** @var Connection $configConnection */
        $configConnection = $this->managerRegistry->getConnection('config');

        if (!SafeDatabaseChecker::tablesExist($configConnection, 'oro_entity_config')) {
            return;
        }

        foreach ($this->getClassNamesOfDeletedEntities() as $className) {
            if (!class_exists($className, false)) {
                $query = new ParametrizedSqlMigrationQuery(
                    'DELETE FROM oro_entity_config WHERE class_name = :class_name',
                    ['class_name' => $className],
                    ['class_name' => Types::STRING]
                );
                $query->setConnection($configConnection);
                $query->execute($this->logger);
            }
        }
    }
}
