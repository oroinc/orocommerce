<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Composite primary key fields order:
 *  - priceList
 *  - website
 */
class PriceListToWebsiteRepository extends EntityRepository
{
    /**
     * @param BasePriceList $priceList
     * @param Website $website
     * @return PriceListToWebsite
     */
    public function findByPrimaryKey(BasePriceList $priceList, Website $website)
    {
        return $this->findOneBy(['priceList' => $priceList, 'website' => $website]);
    }

    /**
     * @param Website $website
     * @return PriceListToWebsite[]
     */
    public function getPriceLists(Website $website)
    {
        $qb = $this->createQueryBuilder('relation');
        $qb->innerJoin('relation.priceList', 'priceList')
            ->where($qb->expr()->eq('relation.website', ':website'))
            ->andWhere($qb->expr()->eq('priceList.active', ':active'))
            ->orderBy('relation.sortOrder', PriceListCollectionType::DEFAULT_ORDER)
            ->setParameter('website', $website)
            ->setParameter('active', true);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return BufferedQueryResultIteratorInterface|Website[]
     */
    public function getWebsiteIteratorWithDefaultFallback()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('distinct website')
            ->from(Website::class, 'website')
            ->leftJoin(
                PriceListToWebsite::class,
                'plToWebsite',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('plToWebsite.website', 'website')
                )
            )
            ->leftJoin(
                PriceListWebsiteFallback::class,
                'priceListFallBack',
                Join::WITH,
                $qb->expr()->eq('priceListFallBack.website', 'website')
            )
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('priceListFallBack.fallback', ':websiteFallback'),
                    $qb->expr()->isNull('priceListFallBack.fallback')
                )
            )
            ->setParameter('websiteFallback', PriceListWebsiteFallback::CONFIG);

        return new BufferedIdentityQueryResultIterator($qb->getQuery());
    }

    /**
     * @return BufferedQueryResultIteratorInterface|Website[]
     */
    public function getWebsiteIteratorWithSelfFallback()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('distinct website')
            ->from(Website::class, 'website')
            ->innerJoin(
                PriceListWebsiteFallback::class,
                'priceListFallBack',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('priceListFallBack.website', 'website')
                )
            )
            ->where(
                $qb->expr()->eq('priceListFallBack.fallback', ':websiteFallback')
            )
            ->setParameter('websiteFallback', PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY);

        return new BufferedIdentityQueryResultIterator($qb->getQuery());
    }

    /**
     * @param PriceList $priceList
     *
     * @return BufferedQueryResultIteratorInterface Each item is an array with the following properties:
     *                                              website - contains website ID
     */
    public function getIteratorByPriceList(PriceList $priceList)
    {
        return $this->getIteratorByPriceLists([$priceList]);
    }

    /**
     * @param PriceList[] $priceLists
     *
     * @return BufferedQueryResultIteratorInterface Each item is an array with the following properties:
     *                                              website - contains website ID
     */
    public function getIteratorByPriceLists($priceLists)
    {
        $qb = $this->createQueryBuilder('priceListToWebsite');

        $qb
            ->select('IDENTITY(priceListToWebsite.website) as website')
            ->where($qb->expr()->in('priceListToWebsite.priceList', ':priceLists'))
            ->groupBy('priceListToWebsite.website')
            ->setParameter('priceLists', $priceLists)
            // order required for BufferedIdentityQueryResultIterator on PostgreSql
            ->orderBy('priceListToWebsite.website');

        return new BufferedQueryResultIterator($qb);
    }

    /**
     * @param Website $website
     * @return mixed
     */
    public function delete(Website $website)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete($this->getEntityName(), 'PriceListToWebsite')
            ->andWhere('PriceListToWebsite.website = :website')
            ->setParameter('website', $website)
            ->getQuery()
            ->execute();
    }

    public function hasAssignedPriceLists(Website $website): bool
    {
        $qb = $this->createQueryBuilder('p');

        $qb->select('p.id')
            ->where($qb->expr()->eq('p.website', ':website'))
            ->setParameter('website', $website)
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult() !== null;
    }
}
