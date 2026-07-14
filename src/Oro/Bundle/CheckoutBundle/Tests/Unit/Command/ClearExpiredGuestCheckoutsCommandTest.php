<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Command\ClearExpiredGuestCheckoutsCommand;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Covers the do-while chunk processing loop (multi-batch, transaction commit/rollback).
 */
final class ClearExpiredGuestCheckoutsCommandTest extends TestCase
{
    private const int CHUNK_SIZE = 10000;

    private ManagerRegistry&MockObject $doctrine;

    private ConfigManager&MockObject $configManager;

    private Connection&MockObject $connection;

    private ClearExpiredGuestCheckoutsCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')
            ->willReturn($this->connection);

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($entityManager);

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->method('get')
            ->with('oro_customer.customer_visitor_cookie_lifetime_days')
            ->willReturn(30);

        $this->command = new ClearExpiredGuestCheckoutsCommand($this->doctrine, $this->configManager);
    }

    public function testExecuteProcessesMultipleBatchesWhenFirstBatchIsFull(): void
    {
        $selectQb1 = $this->mockSelectQb($this->generateRows(self::CHUNK_SIZE));
        $deleteCheckoutQb1 = $this->mockDeleteQb();
        $deleteSourceQb1 = $this->mockDeleteQb();

        $selectQb2 = $this->mockSelectQb($this->generateRows(3));
        $deleteCheckoutQb2 = $this->mockDeleteQb();
        $deleteSourceQb2 = $this->mockDeleteQb();

        $this->connection->expects(self::exactly(6))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $selectQb1,
                $deleteCheckoutQb1,
                $deleteSourceQb1,
                $selectQb2,
                $deleteCheckoutQb2,
                $deleteSourceQb2
            );

        $this->connection->expects(self::exactly(2))->method('beginTransaction');
        $this->connection->expects(self::exactly(2))->method('commit');
        $this->connection->expects(self::never())->method('rollBack');

        $exitCode = (new CommandTester($this->command))->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteStopsAfterSingleBatchWhenItIsNotFull(): void
    {
        $selectQb = $this->mockSelectQb($this->generateRows(5));
        $deleteCheckoutQb = $this->mockDeleteQb();
        $deleteSourceQb = $this->mockDeleteQb();

        $this->connection->expects(self::exactly(3))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($selectQb, $deleteCheckoutQb, $deleteSourceQb);

        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::once())->method('commit');

        $commandTester = new CommandTester($this->command);
        $exitCode = $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Clear expired guest checkouts completed', $commandTester->getDisplay());
    }

    public function testExecuteStopsImmediatelyWhenNothingToDelete(): void
    {
        $selectQb = $this->mockSelectQb([]);

        $this->connection->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($selectQb);

        $this->connection->expects(self::never())->method('beginTransaction');

        $exitCode = (new CommandTester($this->command))->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteRollsBackAndRethrowsWhenBatchDeletionFails(): void
    {
        $selectQb = $this->mockSelectQb($this->generateRows(5));

        $deleteCheckoutQb = $this->createMock(QueryBuilder::class);
        $deleteCheckoutQb->method('delete')->willReturnSelf();
        $deleteCheckoutQb->method('where')->willReturnSelf();
        $deleteCheckoutQb->method('setParameter')->willReturnSelf();
        $deleteCheckoutQb->method('execute')
            ->willThrowException(new \RuntimeException('DB error'));

        $this->connection->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls($selectQb, $deleteCheckoutQb);

        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::once())->method('rollBack');
        $this->connection->expects(self::never())->method('commit');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB error');

        (new CommandTester($this->command))->execute([]);
    }

    private function mockSelectQb(array $rows): QueryBuilder&MockObject
    {
        $result = $this->createMock(Result::class);
        $result->expects(self::once())->method('fetchAllAssociative')->willReturn($rows);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('leftJoin')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        $qb->method('expr')->willReturn($this->createMock(ExpressionBuilder::class));
        $qb->expects(self::once())->method('execute')->willReturn($result);

        return $qb;
    }

    private function mockDeleteQb(): QueryBuilder&MockObject
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('delete')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->expects(self::once())->method('execute')->willReturn(0);

        return $qb;
    }

    private function generateRows(int $count): array
    {
        $rows = [];
        for ($id = 1; $id <= $count; $id++) {
            $rows[] = ['checkout_id' => $id, 'source_id' => $id];
        }

        return $rows;
    }
}
