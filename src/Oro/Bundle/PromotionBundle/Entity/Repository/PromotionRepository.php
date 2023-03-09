<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PromotionBundle\Discount\AbstractDiscount;
use Oro\Bundle\PromotionBundle\Discount\DiscountInterface;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Doctrine repository for Promotion entity
 */
class PromotionRepository extends EntityRepository
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
        $criteria,
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

    public function getAvailablePromotions($criteria, ?string $currency, ?int $organization = null): array
    {
        $queryBuilder = $this->getAvailablePromotionsQueryBuilder($criteria, $currency, $organization);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findPromotionByProductSegment(Segment $segment): ?Promotion
    {
        return $this->findOneBy(['productsSegment' => $segment]);
    }

    /**
     * @param array|Collection $ids
     * @return array
     */
    public function getPromotionsWithLabelsByIds($ids)
    {
        $qb = $this->createQueryBuilder('p');
        $qb->join('p.rule', 'rule')
            ->leftJoin('p.labels', 'labels')
            ->where($qb->expr()->in('p.id', ':ids'))
            ->setParameter('ids', $ids);

        $result = [];
        /** @var Promotion $promotion */
        foreach ($qb->getQuery()->getResult() as $promotion) {
            $result[$promotion->getId()] = $promotion;
        }

        return $result;
    }

    /**
     * @param int[] $promotionIds
     *
     * @return array
     *  [
     *      42 => 'Promotion name',
     *      // ...
     *  ]
     */
    public function getPromotionsNamesByIds(array $promotionIds): array
    {
        if (!$promotionIds) {
            return [];
        }

        $qb = $this->createQueryBuilder('p');
        $qb
            ->select('p.id', 'rule.name')
            ->join('p.rule', 'rule')
            ->where($qb->expr()->in('p.id', ':ids'))
            ->setParameter('ids', array_filter($promotionIds), Connection::PARAM_INT_ARRAY);

        return array_column($qb->getQuery()->getArrayResult(), 'name', 'id');
    }
}
