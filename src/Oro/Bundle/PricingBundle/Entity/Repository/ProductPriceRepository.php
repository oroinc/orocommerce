<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductPriceRepository extends BaseProductPriceRepository
{
    const BUFFER_SIZE = 500;

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function deleteGeneratedPrices(PriceList $priceList, Product $product = null)
    {
        $qb = $this->getDeleteQbByPriceList($priceList, $product);
        $qb->andWhere($qb->expr()->isNotNull('productPrice.priceRule'))
            ->getQuery()
            ->execute();
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     */
    public function deleteGeneratedPricesByRule(PriceRule $priceRule, Product $product = null)
    {
        $qb = $this->getDeleteQbByPriceList($priceRule->getPriceList(), $product);
        $qb->andWhere($qb->expr()->eq('productPrice.priceRule', ':priceRule'))
            ->setParameter('priceRule', $priceRule)
            ->getQuery()
            ->execute();
    }

    /**
     * @param PriceList $priceList
     */
    public function deleteInvalidPrices(PriceList $priceList)
    {
        $qb = $this->createQueryBuilder('invalidPrice');
        $qb->select('invalidPrice.id')
            ->leftJoin(
                PriceListToProduct::class,
                'productRelation',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('invalidPrice.priceList', 'productRelation.priceList'),
                    $qb->expr()->eq('invalidPrice.product', 'productRelation.product')
                )
            )
            ->where($qb->expr()->eq('invalidPrice.priceList', ':priceList'))
            ->andWhere($qb->expr()->isNull('productRelation.id'))
            ->setParameter('priceList', $priceList);
        $iterator = new BufferedQueryResultIterator($qb);
        $iterator->setHydrationMode(Query::HYDRATE_SCALAR);
        $iterator->setBufferSize(self::BUFFER_SIZE);

        $ids = [];
        $i = 0;

        $qbDelete = $this->getDeleteQbByPriceList($priceList);
        $qbDelete->andWhere('productPrice.id IN (:ids)');
        foreach ($iterator as $priceId) {
            $i++;
            $ids[] = $priceId;
            if ($i % self::BUFFER_SIZE === 0) {
                $qbDelete->setParameter('ids', $ids)->getQuery()->execute();
                $ids = [];
            }
        }
        if (!empty($ids)) {
            $qbDelete->setParameter('ids', $ids)->getQuery()->execute();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createQBForCopy(BasePriceList $sourcePriceList, BasePriceList $targetPriceList)
    {
        $qb = parent::createQBForCopy($sourcePriceList, $targetPriceList);
        $qb->andWhere($qb->expr()->isNull('productPrice.priceRule'));

        return $qb;
    }
}
