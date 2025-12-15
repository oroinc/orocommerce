<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides product ID chunks for fallback processing.
 */
class ProductFallbackChunkProvider
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private ProductFallbackFieldProviderInterface $fieldProvider
    ) {
    }

    /**
     * @return iterable<int[]>
     */
    public function getProductIdChunks(int $chunkSize): iterable
    {
        $query = $this->createMissingFallbackQueryBuilder()
            ->select('p.id')
            ->orderBy('p.id')
            ->getQuery();
        $query->setHydrationMode(AbstractQuery::HYDRATE_SCALAR);

        $iterator = new BufferedIdentityQueryResultIterator($query);
        $iterator->setBufferSize($chunkSize);
        $iterator->setHydrationMode(AbstractQuery::HYDRATE_SCALAR);

        $chunk = [];
        foreach ($iterator as $row) {
            $chunk[] = (int) $row['id'];
            if (\count($chunk) === $chunkSize) {
                yield $chunk;
                $chunk = [];
            }
        }

        if ($chunk) {
            yield $chunk;
        }
    }

    public function getPendingProductCount(): int
    {
        $qb = $this->createMissingFallbackQueryBuilder();

        return (int) $qb
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createMissingFallbackQueryBuilder(): QueryBuilder
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Product::class);
        $qb = $em->createQueryBuilder()
            ->select('p')
            ->from(Product::class, 'p');
        $expr = $qb->expr();

        $conditions = [];
        foreach ($this->fieldProvider->getFieldsByFallbackId() as $fields) {
            foreach ($fields as $field) {
                $conditions[] = $expr->isNull(sprintf('p.%s', $field));
            }
        }

        $orExpr = new Expr\Orx($conditions);
        $qb->where($orExpr);

        return $qb;
    }
}
