<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * The repository for Promotion entity.
 */
class PromotionRepository extends ServiceEntityRepository
{
    /**
     * In order to reduce the number of filters (RuleFiltrationServiceInterface) for promotions,
     * limit them using filtering in the query, which works faster than filtering them through filtering services.
     *
     * @return Promotion[]
     */
    public function getAllPromotions(int $organizationId): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.organization = :organizationId')
            ->setParameter('organizationId', $organizationId);

        return $qb->getQuery()->getResult();
    }

    public function getAvailablePromotionsQueryBuilder(
        mixed $criteria,
        ?string $currency,
        ?int $organization = null
    ): QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('promotion');
        $queryBuilder->select('promotion, rule, config');

        // Organization filtration.
        $queryBuilder
            ->where('promotion.organization = :organization')
            ->setParameter('organization', $organization);

        // Enabled filtration.
        $expr = $queryBuilder->expr();
        $queryBuilder
            ->join('promotion.rule', 'rule')
            ->andWhere($expr->eq('rule.enabled', ':enabled'))
            ->setParameter('enabled', true, Types::BOOLEAN)
            ->addOrderBy($queryBuilder->expr()->asc('rule.sortOrder'));

        // Scope filtration.
        $queryBuilder->leftJoin('promotion.scopes', 'scopes', Join::WITH);
        if ($criteria instanceof ScopeCriteria) {
            $criteria->applyWhereWithPriority($queryBuilder, 'scopes');
        }

        // Currency filtration.
        $queryBuilder
            ->join('promotion.discountConfiguration', 'config')
            ->andWhere(
                $expr->orX(
                    $expr->notLike('decode(config.options)', ':discountType'),
                    $expr->like('decode(config.options)', ':discountCurrency')
                )
            )
            ->setParameter(
                'discountType',
                sprintf('%%"%s";s:6:"%s"%%', AbstractDiscount::DISCOUNT_TYPE, DiscountInterface::TYPE_AMOUNT),
                Types::STRING
            )
            ->setParameter(
                'discountCurrency',
                sprintf('%%"%s";s:3:"%s"%%', AbstractDiscount::DISCOUNT_CURRENCY, $currency),
                Types::STRING
            );

        // Schedule filtration.
        $queryBuilder
            ->leftJoin('promotion.schedules', 'schedule')
            ->andWhere(
                $expr->andX(
                    $expr->orX(
                        $expr->isNull('schedule.activeAt'),
                        $expr->lte('schedule.activeAt', ':now')
                    ),
                    $expr->orX(
                        $expr->isNull('schedule.deactivateAt'),
                        $expr->gte('schedule.deactivateAt', ':now')
                    )
                )
            )
            ->setParameter('now', new \DateTime('now', new \DateTimeZone('UTC')));

        $queryBuilder->addOrderBy($queryBuilder->expr()->asc('promotion.id'));

        return $queryBuilder;
    }

    public function getAvailablePromotions(mixed $criteria, ?string $currency, int $organization = null): array
    {
        $queryBuilder = $this->getAvailablePromotionsQueryBuilder($criteria, $currency, $organization);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findPromotionByProductSegment(Segment $segment): ?Promotion
    {
        return $this->findOneBy(['productsSegment' => $segment]);
    }

    /**
     * @param int[] $ids
     *
     * @return Promotion[] [promotion id => promotion, ...]
     */
    public function getPromotionsWithLabelsByIds(array $ids): array
    {
        /** @var Promotion[] $promotions */
        $promotions = $this->createQueryBuilder('p')
            ->join('p.rule', 'rule')
            ->leftJoin('p.labels', 'labels')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($promotions as $promotion) {
            $result[$promotion->getId()] = $promotion;
        }

        return $result;
    }

    /**
     * @param int[] $ids
     *
     * @return string[] [promotion id => promotion name, ...]
     */
    public function getPromotionsNamesByIds(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('p.id', 'rule.name')
            ->join('p.rule', 'rule')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', array_filter($ids), Connection::PARAM_INT_ARRAY)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'name', 'id');
    }
}
