<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

class PriceListStateManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function applyDefault(PriceList $priceList)
    {
        $this->dropDefaultState($priceList);
        $this->setDefaultState($priceList);
    }

    /**
     * @param PriceList $priceList
     */
    protected function dropDefaultState(PriceList $priceList)
    {
        $qb = $this->doctrineHelper->getEntityRepository($priceList)->createQueryBuilder('pl');

        $qb
            ->update()
            ->set('pl.default', ':defaultValue')
            ->setParameter('defaultValue', false)
            ->where($qb->expr()->eq('pl.default', ':oldValue'))
            ->setParameter('oldValue', true)
            ->getQuery()
            ->execute();
    }

    /**
     * @param PriceList $priceList
     */
    protected function setDefaultState(PriceList $priceList)
    {
        $qb = $this->doctrineHelper->getEntityRepository($priceList)->createQueryBuilder('pl');

        $qb
            ->update()
            ->set('pl.default', ':newValue')
            ->setParameter('newValue', true)
            ->where($qb->expr()->eq('pl', ':entity'))
            ->setParameter('entity', $priceList)
            ->getQuery()
            ->execute();
    }
}
