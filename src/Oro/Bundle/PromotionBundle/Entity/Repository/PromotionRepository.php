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
        if (null !== $organization) {
            $queryBuilder
                ->where('promotion.organization = :organizationId')
                ->setParameter('organizationId', $organization);
        }

        // Enabled filtration.
        $queryBuilder
            ->join('promotion.rule', 'rule')
            ->andWhere('rule.enabled = :enabled')
            ->setParameter('enabled', true, Types::BOOLEAN)
            ->addOrderBy('rule.sortOrder');

        // Scope filtration.
        $queryBuilder->leftJoin('promotion.scopes', 'scopes', Join::WITH);
        if ($criteria instanceof ScopeCriteria) {
            $criteria->applyWhereWithPriority($queryBuilder, 'scopes');
        }

        // Currency filtration.
        $queryBuilder
            ->join('promotion.discountConfiguration', 'config')
            ->andWhere(
                'DECODE(config.options) NOT LIKE :discountType OR DECODE(config.options) LIKE :discountCurrency'
            )
            ->setParameter(
                'discountType',
                self::getDiscountConfigParamValue(AbstractDiscount::DISCOUNT_TYPE, DiscountInterface::TYPE_AMOUNT),
                Types::STRING
            )
            ->setParameter(
                'discountCurrency',
                self::getDiscountConfigParamValue(AbstractDiscount::DISCOUNT_CURRENCY, $currency),
                Types::STRING
            );

        // Schedule filtration.
        $queryBuilder
            ->leftJoin('promotion.schedules', 'schedule')
            ->andWhere(
                'schedule.activeAt IS NULL AND schedule.deactivateAt IS NULL'
                . ' OR schedule.activeAt <= :now AND (schedule.deactivateAt >= :now OR schedule.deactivateAt IS NULL)'
                . ' OR schedule.activeAt IS NULL AND  schedule.deactivateAt >= :now'
            )
            ->setParameter('now', new \DateTime('now', new \DateTimeZone('UTC')));

        $queryBuilder->addOrderBy('promotion.id');

        return $queryBuilder;
    }

    public function getAvailablePromotions(mixed $criteria, ?string $currency, int $organization = null): array
    {
        return $this->getAvailablePromotionsQueryBuilder($criteria, $currency, $organization)
            ->getQuery()
            ->getResult();
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

    private static function getDiscountConfigParamValue(string $name, ?string $value): string
    {
        if (null === $value) {
            return sprintf('%%"%s";N:%%', $name);
        }

        return sprintf('%%"%s";s:%d:"%s"%%', $name, \strlen($value), $value);
    }
}
