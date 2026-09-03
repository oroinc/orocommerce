<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Manager;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Bundle\ProductBundle\Provider\ProductFallbackChunkProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ProductFallbackUpdateManagerTest extends TestCase
{
    private const string SEQUENCE = 'public.oro_entity_fallback_value_id_seq';
    private const string QUOTED_SEQUENCE = "'public.oro_entity_fallback_value_id_seq'";

    private ManagerRegistry&MockObject $doctrine;
    private ProductFallbackChunkProvider&MockObject $chunkProvider;
    private LoggerInterface&MockObject $logger;
    private EntityManagerInterface&MockObject $em;
    private Connection&MockObject $connection;
    private ClassMetadata&MockObject $productMetadata;
    private ProductFallbackUpdateManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->chunkProvider = $this->createMock(ProductFallbackChunkProvider::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->productMetadata = $this->createMock(ClassMetadata::class);

        $this->manager = new ProductFallbackUpdateManager(
            $this->doctrine,
            $this->chunkProvider,
            $this->logger
        );
    }

    public function testProcessChunkWithEmptyArrayReturnsZeroWithoutResolvingEntityManager(): void
    {
        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        self::assertSame(0, $this->manager->processChunk([]));
    }

    public function testProcessChunkReturnsZeroWithoutStatementWhenNoFallbackFieldIsMapped(): void
    {
        $this->expectEntityManager();
        $this->chunkProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn([]);

        $this->connection->expects(self::never())
            ->method('executeStatement');

        self::assertSame(0, $this->manager->processChunk([1, 2]));
    }

    public function testProcessChunkPopulatesEveryMappedFieldWithASingleStatement(): void
    {
        $this->expectEntityManager();
        $this->expectFallbackFields([
            'themeConfiguration' => ['pageTemplate' => 'pagetemplate_id'],
            'category' => ['manageInventory' => 'manageinventory_id'],
        ]);
        $this->expectSequence(self::SEQUENCE);

        $expectedSql = 'WITH pairs AS MATERIALIZED ('
            . ' SELECT p.id AS product_id,'
            . ' CASE WHEN p.pagetemplate_id IS NULL'
            . ' THEN nextval(' . self::QUOTED_SEQUENCE . '::regclass) END AS fallback_0,'
            . ' CASE WHEN p.manageinventory_id IS NULL'
            . ' THEN nextval(' . self::QUOTED_SEQUENCE . '::regclass) END AS fallback_1'
            . ' FROM oro_product p WHERE p.id IN (:ids)'
            . '), inserted AS ('
            . ' INSERT INTO oro_entity_fallback_value (id, fallback, array_value)'
            . ' SELECT pairs.fallback_0, :fallback_0_fallback, :arrayValue'
            . ' FROM pairs WHERE pairs.fallback_0 IS NOT NULL'
            . ' UNION ALL'
            . ' SELECT pairs.fallback_1, :fallback_1_fallback, :arrayValue'
            . ' FROM pairs WHERE pairs.fallback_1 IS NOT NULL'
            . ')'
            . ' UPDATE oro_product p SET'
            . ' pagetemplate_id = COALESCE(p.pagetemplate_id, pairs.fallback_0),'
            . ' manageinventory_id = COALESCE(p.manageinventory_id, pairs.fallback_1)'
            . ' FROM pairs WHERE p.id = pairs.product_id'
            . ' AND (pairs.fallback_0 IS NOT NULL OR pairs.fallback_1 IS NOT NULL)';

        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->with(
                $expectedSql,
                [
                    'ids' => [1, 2],
                    'arrayValue' => null,
                    'fallback_0_fallback' => 'themeConfiguration',
                    'fallback_1_fallback' => 'category',
                ],
                ['ids' => ArrayParameterType::INTEGER, 'arrayValue' => 'array']
            )
            ->willReturn(2);

        $this->logger->expects(self::once())
            ->method('info')
            ->with(
                'Product fallback chunk processed successfully',
                ['updated_count' => 2, 'chunk_size' => 2]
            );
        $this->logger->expects(self::never())
            ->method('error');

        self::assertSame(2, $this->manager->processChunk([1, 2]));
    }

    public function testProcessChunkSkipsAFieldThatIsNotAnAssociationOfProduct(): void
    {
        $this->expectEntityManager();
        $this->expectFallbackFields([
            'category' => ['manageInventory' => 'manageinventory_id', 'notAnAssociation' => null],
        ]);
        $this->expectSequence(self::SEQUENCE);

        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $parameters) {
                self::assertStringContainsString('manageinventory_id', $sql);
                self::assertStringNotContainsString('notAnAssociation', $sql);
                self::assertStringNotContainsString('fallback_1', $sql);
                self::assertArrayNotHasKey('fallback_1_fallback', $parameters);

                return 1;
            });

        self::assertSame(1, $this->manager->processChunk([1]));
    }

    public function testProcessChunkDoesNotLogSuccessWhenNoProductWasUpdated(): void
    {
        $this->expectEntityManager();
        $this->expectFallbackFields(['category' => ['manageInventory' => 'manageinventory_id']]);
        $this->expectSequence(self::SEQUENCE);

        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->willReturn(0);

        $this->logger->expects(self::never())
            ->method('info');

        self::assertSame(0, $this->manager->processChunk([1]));
    }

    public function testProcessChunkFailsWhenTheFallbackValueIdentifierHasNoSequence(): void
    {
        $this->expectEntityManager();
        $this->expectFallbackFields(['category' => ['manageInventory' => 'manageinventory_id']]);
        $this->expectSequence(null);

        $this->connection->expects(self::never())
            ->method('executeStatement');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'The identifier of "oro_entity_fallback_value"."id" is not backed by a sequence.'
        );

        $this->manager->processChunk([1]);
    }

    public function testProcessChunkLogsAndRethrowsWhenTheStatementFails(): void
    {
        $this->expectEntityManager();
        $this->expectFallbackFields(['category' => ['manageInventory' => 'manageinventory_id']]);
        $this->expectSequence(self::SEQUENCE);

        $failure = new \RuntimeException('statement failed');
        $this->connection->expects(self::once())
            ->method('executeStatement')
            ->willThrowException($failure);

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Failed to populate product fallback values', self::arrayHasKey('exception'));
        $this->logger->expects(self::never())
            ->method('info');

        $this->expectExceptionObject($failure);

        $this->manager->processChunk([1]);
    }

    public function testGetProductIdChunksRejectsANonPositiveChunkSize(): void
    {
        $this->chunkProvider->expects(self::never())
            ->method('getProductIdChunks');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Chunk size must be a positive integer.');

        iterator_to_array($this->manager->getProductIdChunks(0));
    }

    public function testGetProductIdChunksYieldsTheChunksOfTheProvider(): void
    {
        $this->chunkProvider->expects(self::once())
            ->method('getProductIdChunks')
            ->with(2)
            ->willReturn(new \ArrayIterator([[1, 2], [3]]));

        self::assertSame([[1, 2], [3]], iterator_to_array($this->manager->getProductIdChunks(2)));
    }

    public function testGetPendingProductCountIsDelegatedToTheProvider(): void
    {
        $this->chunkProvider->expects(self::once())
            ->method('getPendingProductCount')
            ->willReturn(7);

        self::assertSame(7, $this->manager->getPendingProductCount());
    }

    /**
     * @dataProvider hasPendingProductsDataProvider
     */
    public function testHasPendingProducts(int $pendingCount, bool $expected): void
    {
        $this->chunkProvider->expects(self::once())
            ->method('getPendingProductCount')
            ->willReturn($pendingCount);

        self::assertSame($expected, $this->manager->hasPendingProducts());
    }

    public static function hasPendingProductsDataProvider(): array
    {
        return [
            'no pending products' => ['pendingCount' => 0, 'expected' => false],
            'pending products' => ['pendingCount' => 1, 'expected' => true],
        ];
    }

    private function expectEntityManager(): void
    {
        $fallbackMetadata = $this->createMock(ClassMetadata::class);
        $fallbackMetadata->expects(self::any())
            ->method('getTableName')
            ->willReturn('oro_entity_fallback_value');
        $fallbackMetadata->expects(self::any())
            ->method('getSingleIdentifierColumnName')
            ->willReturn('id');
        $fallbackMetadata->expects(self::any())
            ->method('getColumnName')
            ->willReturnMap([
                [EntityFieldFallbackValue::FALLBACK_PARENT_FIELD, 'fallback'],
                [EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD, 'array_value'],
            ]);
        $fallbackMetadata->expects(self::any())
            ->method('getTypeOfField')
            ->with(EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD)
            ->willReturn('array');

        $this->productMetadata->expects(self::any())
            ->method('getTableName')
            ->willReturn('oro_product');

        $this->em->expects(self::any())
            ->method('getClassMetadata')
            ->willReturnCallback(fn (string $className): ClassMetadata => match ($className) {
                Product::class => $this->productMetadata,
                EntityFieldFallbackValue::class => $fallbackMetadata,
            });
        $this->em->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->em);
    }

    /**
     * @param array<string, array<string, string|null>> $fieldsByFallbackId
     *        fallback id => [field name => join column, or null when the field is not an association]
     */
    private function expectFallbackFields(array $fieldsByFallbackId): void
    {
        $joinColumns = [];
        $provided = [];
        foreach ($fieldsByFallbackId as $fallbackId => $fields) {
            $provided[$fallbackId] = array_keys($fields);
            foreach ($fields as $fieldName => $joinColumn) {
                $joinColumns[$fieldName] = $joinColumn;
            }
        }

        $this->chunkProvider->expects(self::once())
            ->method('getFieldsByFallbackId')
            ->willReturn($provided);

        $this->productMetadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnCallback(static fn (string $field): bool => ($joinColumns[$field] ?? null) !== null);
        $this->productMetadata->expects(self::any())
            ->method('getSingleAssociationJoinColumnName')
            ->willReturnCallback(static fn (string $field): string => $joinColumns[$field]);
    }

    private function expectSequence(?string $sequence): void
    {
        $this->connection->expects(self::once())
            ->method('fetchOne')
            ->with('SELECT pg_get_serial_sequence(?, ?)', ['oro_entity_fallback_value', 'id'])
            ->willReturn($sequence);

        if (null !== $sequence) {
            $this->connection->expects(self::once())
                ->method('quote')
                ->with($sequence)
                ->willReturn("'" . $sequence . "'");
        }
    }
}
