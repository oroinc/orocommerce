<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\InstallerBundle\Enum\DatabasePlatform;
use Oro\Bundle\InstallerBundle\Provider\AbstractRequirementsProvider;
use Oro\Bundle\PricingBundle\ORM\MySqlTempTableManipulator;
use Oro\Component\DoctrineUtils\DBAL\DbPrivilegesProvider;
use Symfony\Requirements\RequirementCollection;

/**
 * "TEMPORARY" privilege requirement provider for MySQL
 *
 * @see MySqlTempTableManipulator::createTempTableForEntity()
 */
class MysqlTemporaryPrivilegeRequirementProvider extends AbstractRequirementsProvider
{
    protected ManagerRegistry $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @inheritDoc
     */
    public function getOroRequirements(): ?RequirementCollection
    {
        $collection = new RequirementCollection();

        foreach ($this->registry->getConnections() as $name => $connection) {
            /** @var Connection $connection */
            $this->addTemporaryPrivilegeRequirement($collection, $connection, $name);
        }

        return $collection;
    }

    protected function addTemporaryPrivilegeRequirement(
        RequirementCollection $collection,
        Connection $connection,
        string $connectionName
    ): void {
        try {
            if ($connection->getDatabasePlatform()->getName() === DatabasePlatform::MYSQL) {
                $grantedPrivileges = $this->getGrantedPrivileges($connection);
                $fulfilled = array_intersect(['ALL PRIVILEGES', 'CREATE TEMPORARY TABLES'], $grantedPrivileges) !== [];

                $collection->addRequirement(
                    $fulfilled,
                    sprintf(
                        'Connection "%s": "CREATE TEMPORARY TABLES" privilege is granted',
                        $connectionName
                    ),
                    sprintf(
                        'Connection "%s": "CREATE TEMPORARY TABLES" privilege must be granted',
                        $connectionName
                    )
                );
            }
        } catch (Exception $exception) {
            //
        }
    }

    protected function getGrantedPrivileges(Connection $connection): array
    {
        return DbPrivilegesProvider::getMySqlGrantedPrivileges(
            $connection->getWrappedConnection(),
            $connection->getDatabase()
        );
    }
}
