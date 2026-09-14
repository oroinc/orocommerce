<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Manager;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductFallbackChunkProvider;
use Psr\Log\LoggerInterface;

/**
 * Detects and populates the product fallback fields in chunks.
 */
class ProductFallbackUpdateManager
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private ProductFallbackChunkProvider $chunkProvider,
        private LoggerInterface $logger
    ) {
    }

    /**
     * @return iterable<int[]>
     */
    public function getProductIdChunks(int $chunkSize): iterable
    {
        if ($chunkSize <= 0) {
            throw new \InvalidArgumentException('Chunk size must be a positive integer.');
        }

        yield from $this->chunkProvider->getProductIdChunks($chunkSize);
    }

    /**
     * Only the empty fields are filled, so an interrupted run can be repeated safely.
     */
    public function processChunk(array $productIds): int
    {
        if (!$productIds) {
            return 0;
        }

        $em = $this->getEntityManager();
        $fields = $this->getFallbackFieldsMapping($em);
        if (!$fields) {
            return 0;
        }

        $parameters = ['ids' => $productIds, 'arrayValue' => null];
        foreach ($fields as $alias => $field) {
            $parameters[$alias . '_fallback'] = $field['fallbackId'];
        }

        // The type comes from the mapping, so the empty value is stored exactly as the ORM stores it.
        $types = [
            'ids' => ArrayParameterType::INTEGER,
            'arrayValue' => $em->getClassMetadata(EntityFieldFallbackValue::class)
                ->getTypeOfField(EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD),
        ];

        try {
            $updatedProducts = (int) $em->getConnection()->executeStatement(
                $this->getPopulateFallbackSql($em, $fields),
                $parameters,
                $types
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to populate product fallback values',
                ['exception' => $e, 'product_ids' => $productIds]
            );
            throw $e;
        }

        if ($updatedProducts > 0) {
            $this->logger->info(
                'Product fallback chunk processed successfully',
                ['updated_count' => $updatedProducts, 'chunk_size' => count($productIds)]
            );
        }

        return $updatedProducts;
    }

    public function getPendingProductCount(): int
    {
        return $this->chunkProvider->getPendingProductCount();
    }

    public function hasPendingProducts(): bool
    {
        return $this->chunkProvider->getPendingProductCount() > 0;
    }

    /**
     * The insert and the link fit into one statement because the foreign keys are checked once it completes.
     * The sequence is a `regclass` constant: resolving it inline costs a catalog lookup per row and field.
     */
    private function getPopulateFallbackSql(EntityManagerInterface $em, array $fields): string
    {
        $productTable = $em->getClassMetadata(Product::class)->getTableName();
        $fallbackMetadata = $em->getClassMetadata(EntityFieldFallbackValue::class);
        $nextValue = sprintf(
            'nextval(%s::regclass)',
            $em->getConnection()->quote($this->getFallbackValueSequence($em))
        );

        $reservations = [];
        $insertions = [];
        $assignments = [];
        $conditions = [];
        foreach ($fields as $alias => $field) {
            $reservations[] = sprintf(
                'CASE WHEN p.%s IS NULL THEN %s END AS %s',
                $field['column'],
                $nextValue,
                $alias
            );
            $insertions[] = sprintf(
                'SELECT pairs.%1$s, :%1$s_fallback, :arrayValue FROM pairs WHERE pairs.%1$s IS NOT NULL',
                $alias
            );
            $assignments[] = sprintf('%1$s = COALESCE(p.%1$s, pairs.%2$s)', $field['column'], $alias);
            $conditions[] = sprintf('pairs.%s IS NOT NULL', $alias);
        }

        return sprintf(
            'WITH pairs AS MATERIALIZED ('
            . ' SELECT p.id AS product_id, %2$s FROM %1$s p WHERE p.id IN (:ids)'
            . '), inserted AS ('
            . ' INSERT INTO %3$s (%4$s, %5$s, %6$s) %7$s'
            . ')'
            . ' UPDATE %1$s p SET %8$s FROM pairs WHERE p.id = pairs.product_id AND (%9$s)',
            $productTable,
            implode(', ', $reservations),
            $fallbackMetadata->getTableName(),
            $fallbackMetadata->getSingleIdentifierColumnName(),
            $fallbackMetadata->getColumnName(EntityFieldFallbackValue::FALLBACK_PARENT_FIELD),
            $fallbackMetadata->getColumnName(EntityFieldFallbackValue::FALLBACK_ARRAY_FIELD),
            implode(' UNION ALL ', $insertions),
            implode(', ', $assignments),
            implode(' OR ', $conditions)
        );
    }

    private function getFallbackValueSequence(EntityManagerInterface $em): string
    {
        $metadata = $em->getClassMetadata(EntityFieldFallbackValue::class);
        $table = $metadata->getTableName();
        $column = $metadata->getSingleIdentifierColumnName();

        $sequence = $em->getConnection()->fetchOne(
            'SELECT pg_get_serial_sequence(?, ?)',
            [$table, $column]
        );

        if (!\is_string($sequence) || '' === $sequence) {
            throw new \RuntimeException(
                sprintf('The identifier of "%s"."%s" is not backed by a sequence.', $table, $column)
            );
        }

        return $sequence;
    }

    private function getFallbackFieldsMapping(EntityManagerInterface $em): array
    {
        $productMetadata = $em->getClassMetadata(Product::class);

        $fields = [];
        $index = 0;
        foreach ($this->chunkProvider->getFieldsByFallbackId() as $fallbackId => $fieldNames) {
            foreach ($fieldNames as $fieldName) {
                if (!$productMetadata->hasAssociation($fieldName)) {
                    continue;
                }

                $fields['fallback_' . $index++] = [
                    'column' => $productMetadata->getSingleAssociationJoinColumnName($fieldName),
                    'fallbackId' => $fallbackId,
                ];
            }
        }

        return $fields;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass(Product::class);
    }
}
