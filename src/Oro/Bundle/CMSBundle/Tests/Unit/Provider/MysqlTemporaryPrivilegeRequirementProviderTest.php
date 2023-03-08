<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Provider\MysqlTemporaryPrivilegeRequirementProvider;
use Oro\Bundle\InstallerBundle\Enum\DatabasePlatform;
use Oro\Component\Testing\Unit\ORM\Mocks\ConnectionMock;
use Oro\Component\Testing\Unit\ORM\Mocks\DatabasePlatformMock;
use Oro\Component\Testing\Unit\ORM\Mocks\DriverMock;

class MysqlTemporaryPrivilegeRequirementProviderTest extends \PHPUnit\Framework\TestCase
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

    protected function getDoctrine(): ManagerRegistry
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getConnectionNames')
            ->willReturn(['default' => 'doctrine.dbal.default_connection']);
        $doctrine->expects(self::any())
            ->method('getConnections')
            ->willReturn(['default' => $this->getConnection()]);

        return $doctrine;
    }

    protected function getConnection(): ConnectionMock
    {
        $platform = new DatabasePlatformMock();
        $platform->setName(DatabasePlatform::MYSQL);

        $connection = new ConnectionMock([], new DriverMock());
        $connection->setDatabasePlatform($platform);

        return $connection;
    }

    protected function getProviderWithoutPrivilege(): MysqlTemporaryPrivilegeRequirementProvider
    {
        return new class($this->getDoctrine()) extends MysqlTemporaryPrivilegeRequirementProvider {
            protected function getGrantedPrivileges(Connection $connection): array
            {
                return [];
            }
        };
    }

    protected function getProviderWithPrivilege(): MysqlTemporaryPrivilegeRequirementProvider
    {
        return new class($this->getDoctrine()) extends MysqlTemporaryPrivilegeRequirementProvider {
            protected function getGrantedPrivileges(Connection $connection): array
            {
                return ['CREATE TEMPORARY TABLES'];
            }
        };
    }
}
