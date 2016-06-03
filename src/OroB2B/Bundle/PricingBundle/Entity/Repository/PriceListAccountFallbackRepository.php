<?php

namespace OroB2B\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountFallback;

class PriceListAccountFallbackRepository extends EntityRepository
{
    /**
     * @param array $accountGroups
     * @param int $websiteId
     * @return BufferedQueryResultIterator|array
     */
    public function getAccountIdentityByGroup(array $accountGroups, $websiteId)
    {
        if (empty($accountGroups)) {
            return [];
        }
        $qb = $this->getBaseQbForFallback($websiteId);

        $qb->andWhere($qb->expr()->in('account.group', ':groups'))
            ->setParameter('groups', $accountGroups, Type::SIMPLE_ARRAY);

        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);

        return $iterator;
    }

    /**
     * @param int $websiteId
     * @return QueryBuilder
     */
    public function getBaseQbForFallback($websiteId)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('DISTINCT account.id')
            ->from('OroB2BAccountBundle:Account', 'account');
        $qb->leftJoin(
            'OroB2BPricingBundle:PriceListAccountFallback',
            'accountFallback',
            Join::WITH,
            $qb->expr()->andX(
                $qb->expr()->eq('account.id', 'accountFallback.account'),
                $qb->expr()->eq('accountFallback.website', ':website')
            )
        )
        ->andWhere(
            $qb->expr()->orX(
                $qb->expr()->isNull('accountFallback.id'),
                $qb->expr()->eq('accountFallback.fallback', ':fallback')
            )
        )
        ->setParameter('website', $websiteId)
        ->setParameter('fallback', PriceListAccountFallback::ACCOUNT_GROUP);

        return $qb;
    }
}
