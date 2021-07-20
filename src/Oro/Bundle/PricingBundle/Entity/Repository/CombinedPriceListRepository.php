<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\BaseCombinedPriceListRelation;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListRelationHelper;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Entity repository for Combined Price List entity
 */
class CombinedPriceListRepository extends BasePriceListRepository
{
    const CPL_BATCH_SIZE = 100;

    /**
     * @param CombinedPriceList $combinedPriceList
     * @return CombinedPriceListToPriceList[]
     */
    public function getPriceListRelations(CombinedPriceList $combinedPriceList)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('partial cpl.{id, priceList, mergeAllowed}')
            ->from('OroPricingBundle:CombinedPriceListToPriceList', 'cpl')
            ->where($qb->expr()->eq('cpl.combinedPriceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList)
            ->orderBy('cpl.sortOrder');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Customer $customer
     * @param Website $website
     * @param bool $isEnabled
     * @return null|CombinedPriceList
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPriceListByCustomer(Customer $customer, Website $website, $isEnabled = true)
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb
            ->innerJoin(
                'OroPricingBundle:CombinedPriceListToCustomer',
                'priceListToCustomer',
                Join::WITH,
                'priceListToCustomer.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToCustomer.customer', ':customer'))
            ->andWhere($qb->expr()->eq('priceListToCustomer.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.enabled', ':enabled'))
            ->setParameter('customer', $customer)
            ->setParameter('website', $website)
            ->setParameter('enabled', $isEnabled)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param CustomerGroup $customerGroup
     * @param Website $website
     * @param bool $isEnabled
     * @return null|CombinedPriceList
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPriceListByCustomerGroup(CustomerGroup $customerGroup, Website $website, $isEnabled = true)
    {
        $qb = $this->createQueryBuilder('priceList');
        $qb
            ->innerJoin(
                'OroPricingBundle:CombinedPriceListToCustomerGroup',
                'priceListToCustomerGroup',
                Join::WITH,
                'priceListToCustomerGroup.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToCustomerGroup.customerGroup', ':customerGroup'))
            ->andWhere($qb->expr()->eq('priceListToCustomerGroup.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.enabled', ':enabled'))
            ->setParameter('customerGroup', $customerGroup)
            ->setParameter('website', $website)
            ->setParameter('enabled', $isEnabled)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Website $website
     * @param bool $isEnabled
     * @return CombinedPriceList|null
     */
    public function getPriceListByWebsite(Website $website, $isEnabled = true)
    {
        $qb = $this->createQueryBuilder('priceList');

        $qb
            ->innerJoin(
                'OroPricingBundle:CombinedPriceListToWebsite',
                'priceListToWebsite',
                Join::WITH,
                'priceListToWebsite.priceList = priceList'
            )
            ->where($qb->expr()->eq('priceListToWebsite.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.enabled', ':enabled'))
            ->setParameter('website', $website)
            ->setParameter('enabled', $isEnabled)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array|CombinedPriceList[] $priceLists
     */
    public function deletePriceLists(array $priceLists)
    {
        $deleteQb = $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'cplDelete');

        $deleteQb->where($deleteQb->expr()->in('cplDelete.id', ':unusedPriceLists'));

        if ($priceLists) {
            $deleteQb->setParameter('unusedPriceLists', $priceLists)
                ->getQuery()->execute();
        }
    }

    /**
     * @return int
     */
    protected function getBufferSize()
    {
        return BufferedIdentityQueryResultIterator::DEFAULT_BUFFER_SIZE;
    }

    /**
     * @param array $exceptPriceLists
     * @param bool|null $priceListsEnabled
     * @return array
     */
    public function getUnusedPriceListsIds(array $exceptPriceLists = [], $priceListsEnabled = true)
    {
        $selectQb = $this->createQueryBuilder('priceList')
            ->select('priceList.id');
        foreach (CombinedPriceListRelationHelper::RELATIONS as $alias => $entityName) {
            $selectQb->leftJoin(
                $entityName,
                $alias,
                Join::WITH,
                $selectQb->expr()->eq($alias . '.priceList', 'priceList.id')
            );
            $selectQb->andWhere($selectQb->expr()->isNull($alias . '.priceList'));

            $fcAlias = $alias . 'fc';
            $selectQb->leftJoin(
                $entityName,
                $fcAlias,
                Join::WITH,
                $selectQb->expr()->eq($fcAlias . '.fullChainPriceList', 'priceList.id')
            );
            $selectQb->andWhere($selectQb->expr()->isNull($fcAlias . '.fullChainPriceList'));
        }
        $selectQb->leftJoin(
            CombinedPriceListActivationRule::class,
            'rule',
            Join::WITH,
            $selectQb->expr()->eq('rule.combinedPriceList', 'priceList.id')
        );
        $selectQb->andWhere($selectQb->expr()->isNull('rule.combinedPriceList'));

        $selectQb->leftJoin(
            CombinedPriceListActivationRule::class,
            'rulefc',
            Join::WITH,
            $selectQb->expr()->eq('rulefc.fullChainPriceList', 'priceList.id')
        );
        $selectQb->andWhere($selectQb->expr()->isNull('rulefc.fullChainPriceList'));

        if ($exceptPriceLists) {
            $selectQb->andWhere($selectQb->expr()->notIn('priceList', ':exceptPriceLists'))
                ->setParameter('exceptPriceLists', $exceptPriceLists);
        }
        if ($priceListsEnabled !== null) {
            $selectQb->andWhere($selectQb->expr()->eq('priceList.enabled', ':isEnabled'))
                ->setParameter('isEnabled', $priceListsEnabled);
        }

        return array_column($selectQb->getQuery()->getScalarResult(), 'id');
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param CombinedPriceList $activeCpl
     * @param Website $website
     * @param Customer|CustomerGroup $targetEntity
     * @return BaseCombinedPriceListRelation
     */
    public function updateCombinedPriceListConnection(
        CombinedPriceList $combinedPriceList,
        CombinedPriceList $activeCpl,
        Website $website,
        $targetEntity = null
    ) {
        $em = $this->getEntityManager();
        $relation = null;
        if ($targetEntity instanceof Customer) {
            $relation = $em->getRepository('OroPricingBundle:CombinedPriceListToCustomer')
                ->findOneBy(['customer' => $targetEntity, 'website' => $website]);
            if (!$relation) {
                $relation = new CombinedPriceListToCustomer();
                $relation->setCustomer($targetEntity);
                $relation->setWebsite($website);
                $relation->setPriceList($combinedPriceList);
                $relation->setFullChainPriceList($combinedPriceList);
                $em->persist($relation);
            }
        } elseif ($targetEntity instanceof CustomerGroup) {
            $relation = $em->getRepository('OroPricingBundle:CombinedPriceListToCustomerGroup')
                ->findOneBy(['customerGroup' => $targetEntity, 'website' => $website]);
            if (!$relation) {
                $relation = new CombinedPriceListToCustomerGroup();
                $relation->setCustomerGroup($targetEntity);
                $relation->setWebsite($website);
                $relation->setPriceList($combinedPriceList);
                $relation->setFullChainPriceList($combinedPriceList);
                $em->persist($relation);
            }
        } elseif (!$targetEntity) {
            $relation = $em->getRepository('OroPricingBundle:CombinedPriceListToWebsite')
                ->findOneBy(['website' => $website]);
            if (!$relation) {
                $relation = new CombinedPriceListToWebsite();
                $relation->setWebsite($website);
                $relation->setPriceList($combinedPriceList);
                $relation->setFullChainPriceList($combinedPriceList);
                $em->persist($relation);
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown target "%s"', get_class($targetEntity)));
        }
        $relation->setFullChainPriceList($combinedPriceList);
        $relation->setPriceList($activeCpl);
        $em->flush($relation);

        return $relation;
    }

    /**
     * @param $exceptRelation
     * @return bool
     */
    public function hasOtherRelations(BaseCombinedPriceListRelation $exceptRelation)
    {
        $mainQb = $this->createQueryBuilder('cpl');
        $mainQb->select('1')
            ->where('cpl = :cpl')
            ->setParameter('cpl', $exceptRelation->getPriceList())
            ->setParameter('website', $exceptRelation->getWebsite());

        $expr = $mainQb->expr()->orX();
        foreach (CombinedPriceListRelationHelper::RELATIONS as $alias => $class) {
            $subQb = $this->getEntityManager()->createQueryBuilder();
            $subQb->select('1')
                ->from($class, $alias)
                ->where($subQb->expr()->eq($alias.'.priceList', ':cpl'))
                ->andWhere($subQb->expr()->eq($alias.'.website', ':website'));
            if (is_a($exceptRelation, $class)) {
                $subQb->andWhere($subQb->expr()->neq($alias.'.id', ':exceptRelation'));
                $mainQb->setParameter('exceptRelation', $exceptRelation->getId());
            }
            $expr->add($mainQb->expr()->exists($subQb->getDQL()));
        }
        $mainQb->andWhere($expr);
        $result = $mainQb->getQuery()->getOneOrNullResult(Query::HYDRATE_SCALAR);

        return !empty($result);
    }

    /**
     * @param PriceList $priceList
     * @param null $hasCalculatedPrices
     * @return BufferedQueryResultIteratorInterface
     */
    public function getCombinedPriceListsByPriceList(PriceList $priceList, $hasCalculatedPrices = null)
    {
        $qb = $this->createQueryBuilder('cpl');

        $qb->select('DISTINCT cpl')
            ->innerJoin(
                'OroPricingBundle:CombinedPriceListToPriceList',
                'priceListRelations',
                Join::WITH,
                $qb->expr()->eq('cpl', 'priceListRelations.combinedPriceList')
            )
            ->where($qb->expr()->eq('priceListRelations.priceList', ':priceList'))
            ->setParameter('priceList', $priceList);
        if ($hasCalculatedPrices !== null) {
            $qb->andWhere($qb->expr()->eq('cpl.pricesCalculated', ':hasCalculatedPrices'))
                ->setParameter('hasCalculatedPrices', $hasCalculatedPrices);
        }

        return new BufferedIdentityQueryResultIterator($qb->getQuery());
    }

    /**
     * @param array|PriceList[]|int[] $priceLists
     * @return BufferedQueryResultIteratorInterface
     */
    public function getCombinedPriceListsByPriceLists(array $priceLists)
    {
        $qb = $this->createQueryBuilder('cpl');

        $qb->select('cpl')
            ->innerJoin(
                CombinedPriceListToPriceList::class,
                'priceListRelations',
                Join::WITH,
                $qb->expr()->eq('cpl', 'priceListRelations.combinedPriceList')
            )
            ->where($qb->expr()->in('priceListRelations.priceList', ':priceLists'))
            ->setParameter('priceLists', $priceLists);

        return new BufferedIdentityQueryResultIterator($qb->getQuery());
    }

    /**
     * @param int $offsetHours
     *
     * @return BufferedQueryResultIteratorInterface|CombinedPriceList[]
     */
    public function getCPLsForPriceCollectByTimeOffset($offsetHours)
    {
        $qb = $this->getCPLsForPriceCollectByTimeOffsetQueryBuilder($offsetHours);
        // Return only not calculated CPLs withing offset hours
        $qb->andWhere($qb->expr()->eq('cpl.pricesCalculated', ':pricesCalculated'))
            ->setParameter('pricesCalculated', false);

        $iterator = new BufferedIdentityQueryResultIterator($qb);
        $iterator->setBufferSize(self::CPL_BATCH_SIZE);

        return $iterator;
    }

    /**
     * @param int $offsetHours
     *
     * @return int
     */
    public function getCPLsForPriceCollectByTimeOffsetCount($offsetHours)
    {
        $qb = $this->getCPLsForPriceCollectByTimeOffsetQueryBuilder($offsetHours);

        return $qb->select('COUNT(cpl.id)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $offsetHours
     *
     * @return QueryBuilder
     */
    protected function getCPLsForPriceCollectByTimeOffsetQueryBuilder($offsetHours)
    {
        $activateDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $activateDate->add(new \DateInterval(sprintf('PT%dM', $offsetHours * 60)));

        $qb = $this->createQueryBuilder('cpl');
        $qb->select('distinct cpl')
            ->join(
                CombinedPriceListActivationRule::class,
                'combinedPriceListActivationRule',
                Join::WITH,
                $qb->expr()->eq('cpl', 'combinedPriceListActivationRule.combinedPriceList')
            )
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->lt('combinedPriceListActivationRule.activateAt', ':activateData'),
                    $qb->expr()->isNull('combinedPriceListActivationRule.activateAt')
                )
            )
            ->andWhere($qb->expr()->eq('combinedPriceListActivationRule.active', ':active'))
            ->setParameter('activateData', $activateDate, Types::DATETIME_MUTABLE)
            ->setParameter('active', false);

        return $qb;
    }
}
