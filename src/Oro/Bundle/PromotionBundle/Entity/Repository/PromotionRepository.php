<?php

namespace Oro\Bundle\PromotionBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
    public function getAvailablePromotionsQueryBuilder(
        ScopeCriteria $criteria,
        ?string $currency,
        int|array|null $organizationId = null
    ): QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('promotion');
        $queryBuilder->select('promotion, rule, config');

        // Organization filtration.
        if (null !== $organizationId) {
            if (!\is_array($organizationId)) {
                $queryBuilder
                    ->where('promotion.organization = :organizationId')
                    ->setParameter('organizationId', $organizationId);
            } elseif ($organizationId) {
                $queryBuilder
                    ->where('promotion.organization IN(:organizationIds)')
                    ->setParameter('organizationIds', $organizationId);
            }
        }

        // Enabled filtration.
        $queryBuilder
            ->join('promotion.rule', 'rule')
            ->andWhere('rule.enabled = :enabled')
            ->setParameter('enabled', true, Types::BOOLEAN)
            ->addOrderBy('rule.sortOrder');

        // Scope filtration.
        $queryBuilder->leftJoin('promotion.scopes', 'scopes', Join::WITH);
        $criteria->applyWhereWithPriority($queryBuilder, 'scopes');

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
                'schedule.activeAt IS NULL OR schedule.activeAt <= :now'
                . ' AND schedule.deactivateAt IS NULL OR schedule.deactivateAt >= :now'
            )
            ->setParameter('now', new \DateTime('now', new \DateTimeZone('UTC')));

        $queryBuilder->addOrderBy('promotion.id');

        return $queryBuilder;
    }

    /**
     * @param ScopeCriteria  $criteria
     * @param string|null    $currency
     * @param int|int[]|null $organizationId
     *
     * @return Promotion[]
     */
    public function getAvailablePromotions(
        ScopeCriteria $criteria,
        ?string $currency,
        int|array|null $organizationId = null
    ): array {
        return $this->getAvailablePromotionsQueryBuilder($criteria, $currency, $organizationId)
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

    private static function getDiscountConfigParamValue(string $name, ?string $value): string
    {
        if (null === $value) {
            return sprintf('%%"%s";N:%%', $name);
        }

        return sprintf('%%"%s";s:%d:"%s"%%', $name, \strlen($value), $value);
    }
}
