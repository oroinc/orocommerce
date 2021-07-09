<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSProBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Provider\MysqlTemporaryPrivilegeRequirementProvider;
use Oro\Bundle\InstallerBundle\Enum\DatabasePlatform;
use Oro\Component\TestUtils\ORM\Mocks\ConnectionMock;
use Oro\Component\TestUtils\ORM\Mocks\DatabasePlatformMock;
use Oro\Component\TestUtils\ORM\Mocks\DriverMock;
use PHPUnit\Framework\TestCase;

class MysqlTemporaryPrivilegeRequirementProviderTest extends TestCase
{
    public function testCollectionSize()
    {
        $provider = $this->getProviderWithoutPrivilege();
        $requirements = $provider->getOroRequirements();

        $this->assertNotNull($requirements);
        $this->assertCount(1, $requirements->all());
    }

    public function testTemporaryPrivilegeNotGranted()
    {
        $provider = $this->getProviderWithoutPrivilege();
        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[0];
        $this->assertFalse($requirement->isFulfilled());
    }

    public function testTemporaryPrivilegeGranted()
    {
        $provider = $this->getProviderWithPrivilege();
        $requirements = $provider->getOroRequirements()->all();

        $requirement = $requirements[0];
        $this->assertTrue($requirement->isFulfilled());
    }

    protected function getManagerRegistryMock(): ManagerRegistry
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getConnectionNames')->willReturn(['default' => 'doctrine.dbal.default_connection']);
        $registry->method('getConnections')->willReturn(['default' => $this->getConnectionMock()]);

        return $registry;
    }

    protected function getConnectionMock(): ConnectionMock
    {
        $platform = new class extends DatabasePlatformMock {
            public function getName(): string
            {
                return DatabasePlatform::MYSQL;
            }
        };
        $connection = new ConnectionMock([], new DriverMock());
        $connection->setDatabasePlatform($platform);

        return $connection;
    }

    protected function getProviderWithoutPrivilege(): MysqlTemporaryPrivilegeRequirementProvider
    {
        return new class($this->getManagerRegistryMock()) extends MysqlTemporaryPrivilegeRequirementProvider {
            protected function getGrantedPrivileges(Connection $connection): array
            {
                return [];
            }
        };
    }

    protected function getProviderWithPrivilege(): MysqlTemporaryPrivilegeRequirementProvider
    {
        return new class($this->getManagerRegistryMock()) extends MysqlTemporaryPrivilegeRequirementProvider {
            protected function getGrantedPrivileges(Connection $connection): array
            {
                return ['CREATE TEMPORARY TABLES'];
            }
        };
    }
}
