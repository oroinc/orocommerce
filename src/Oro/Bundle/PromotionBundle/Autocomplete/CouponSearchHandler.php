<?php

namespace Oro\Bundle\PromotionBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\PromotionBundle\Model\CouponApplicabilityQueryBuilderModifier;

/**
 * The autocomplete handler to search coupons.
 */
class CouponSearchHandler extends SearchHandler
{
    /**
     * @var CouponApplicabilityQueryBuilderModifier
     */
    private $modifier;

    public function setCouponApplicabilityQueryBuilderModifier(CouponApplicabilityQueryBuilderModifier $modifier)
    {
        $this->modifier = $modifier;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $queryBuilder = $this->entityRepository->createQueryBuilder('coupon');
        $queryBuilder
            ->innerJoin('coupon.promotion', 'promotion')
            ->innerJoin('promotion.rule', 'rule')
            ->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->like($queryBuilder->expr()->lower('coupon.code'), ':code'),
                $queryBuilder->expr()->like($queryBuilder->expr()->lower('rule.name'), ':code')
            ))
            ->setParameter('code', '%' . strtolower($search) . '%');

        $this->modifier->modify($queryBuilder);

        $query = $this->aclHelper->apply($queryBuilder);

        return $query->getResult();
    }
}
