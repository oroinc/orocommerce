<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectShardQueryExecutor;
use Oro\Bundle\PricingBundle\ORM\MultiInsertShardQueryExecutor;
use Oro\Bundle\PricingBundle\ORM\QueryExecutorProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QueryExecutorProviderTest extends TestCase
{
    private ManagerRegistry|MockObject $registry;
    private InsertFromSelectShardQueryExecutor|MockObject $insertFromSelectExecutor;
    private MultiInsertShardQueryExecutor|MockObject $multiInsertQueryExecutor;
    private QueryExecutorProvider $provider;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->insertFromSelectExecutor = $this->createMock(InsertFromSelectShardQueryExecutor::class);
        $this->multiInsertQueryExecutor = $this->createMock(MultiInsertShardQueryExecutor::class);
        $this->provider = new QueryExecutorProvider(
            $this->registry,
            $this->insertFromSelectExecutor,
            $this->multiInsertQueryExecutor
        );
    }

    public function testGetQueryExecutorIfsAllowedPg()
    {
        $platform = $this->createMock(PostgreSQL94Platform::class);
        $this->assertPlatformCall($platform);
        $this->assertSame($this->insertFromSelectExecutor, $this->provider->getQueryExecutor());
    }

    public function testGetQueryExecutorIfsDisallowedPg()
    {
        $platform = $this->createMock(PostgreSQL94Platform::class);
        $this->assertPlatformCall($platform);

        $this->provider->setAllowInsertFromSelectExecutorUsage(false);
        $this->assertSame($this->multiInsertQueryExecutor, $this->provider->getQueryExecutor());
    }

    public function testGetQueryExecutorIfsAllowedMySql()
    {
        $platform = $this->createMock(MySqlPlatform::class);
        $this->assertPlatformCall($platform);
        $this->assertSame($this->multiInsertQueryExecutor, $this->provider->getQueryExecutor());
    }

    public function testGetQueryExecutorIfsDisallowedMySql()
    {
        $platform = $this->createMock(MySqlPlatform::class);
        $this->assertPlatformCall($platform);

        $this->provider->setAllowInsertFromSelectExecutorUsage(false);
        $this->assertSame($this->multiInsertQueryExecutor, $this->provider->getQueryExecutor());
    }

    private function assertPlatformCall(AbstractPlatform $platform): void
    {
        $conn = $this->createMock(Connection::class);
        $conn->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);
        $this->registry->expects($this->any())
            ->method('getConnection')
            ->willReturn($conn);
    }
}
