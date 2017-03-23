<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;

class CombinedProductPriceRepository extends BaseProductPriceRepository
{
    /**
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param CombinedPriceList $combinedPriceList
     * @param PriceList $priceList
     * @param boolean $mergeAllowed
     * @param Product|null $product
     */
    public function insertPricesByPriceList(
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        CombinedPriceList $combinedPriceList,
        PriceList $priceList,
        $mergeAllowed,
        Product $product = null
    ) {
        $qb = $this->getEntityManager()
            ->getRepository('OroPricingBundle:ProductPrice')
            ->createQueryBuilder('pp');

        $qb
            ->select(
                'IDENTITY(pp.product)',
                'IDENTITY(pp.unit)',
                (string)$qb->expr()->literal($combinedPriceList->getId()),
                'pp.productSku',
                'pp.quantity',
                'pp.value',
                'pp.currency',
                sprintf('CAST(%d as boolean)', (int)$mergeAllowed)
            )
            ->where($qb->expr()->eq('pp.priceList', ':currentPriceList'))
            ->groupBy('pp.id')
            ->setParameter('currentPriceList', $priceList);

        if ($product) {
            $qb->andWhere($qb->expr()->eq('pp.product', ':currentProduct'))
                ->setParameter('currentProduct', $product);
        }

        $this->addUniquePriceCondition($qb, $combinedPriceList, $mergeAllowed);

        $insertFromSelectQueryExecutor->execute(
            'OroPricingBundle:CombinedProductPrice',
            [
                'product',
                'unit',
                'priceList',
                'productSku',
                'quantity',
                'value',
                'currency',
                'mergeAllowed'
            ],
            $qb
        );
    }

    /**
     * @param CombinedPriceList $combinedPriceList
     * @param Product|null $product
     */
    public function deleteCombinedPrices(CombinedPriceList $combinedPriceList, Product $product = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->getEntityName(), 'combinedPrice')
            ->where($qb->expr()->eq('combinedPrice.priceList', ':combinedPriceList'))
            ->setParameter('combinedPriceList', $combinedPriceList);

        if ($product) {
            $qb->andWhere($qb->expr()->eq('combinedPrice.product', ':product'))
                ->setParameter('product', $product);
        }

        $qb->getQuery()->execute();
    }

    /**
     * @param CombinedPriceList $priceList
     * @param array $productIds
     * @param null|string $currency
     * @return CombinedProductPrice[]
     */
    public function getPricesForProductsByPriceList(CombinedPriceList $priceList, array $productIds, $currency = null)
    {
        if (count($productIds) === 0) {
            return [];
        }

        $qb = $this->createQueryBuilder('cpp');

        $qb->select('cpp')
            ->where($qb->expr()->eq('cpp.priceList', ':priceList'))
            ->andWhere($qb->expr()->in('cpp.product', ':productIds'))
            ->setParameters([
                'priceList' => $priceList,
                'productIds' => $productIds
            ]);

        if ($currency) {
            $qb->andWhere($qb->expr()->eq('cpp.currency', ':currency'))
                ->setParameter('currency', $currency);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     * @param CombinedPriceList $combinedPriceList
     * @param boolean $mergeAllowed
     */
    protected function addUniquePriceCondition(QueryBuilder $qb, CombinedPriceList $combinedPriceList, $mergeAllowed)
    {
        if ($mergeAllowed) {
            $qb->leftJoin(
                'OroPricingBundle:CombinedProductPrice',
                'cpp',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp.priceList', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp.product'),
                    $qb->expr()->eq('cpp.mergeAllowed', ':mergeAllowed')
                )
            )
            ->setParameter('mergeAllowed', false)
            ->leftJoin(
                'OroPricingBundle:CombinedProductPrice',
                'cpp2',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp2.priceList', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp2.product'),
                    $qb->expr()->eq('pp.unit', 'cpp2.unit'),
                    $qb->expr()->eq('pp.quantity', 'cpp2.quantity'),
                    $qb->expr()->eq('pp.currency', 'cpp2.currency')
                )
            )
            ->andWhere($qb->expr()->isNull('cpp2.id'));
        } else {
            $qb->leftJoin(
                'OroPricingBundle:CombinedProductPrice',
                'cpp',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('cpp.priceList', ':combinedPriceList'),
                    $qb->expr()->eq('pp.product', 'cpp.product')
                )
            );
        }

        $qb->andWhere($qb->expr()->isNull('cpp.id'))
            ->setParameter('combinedPriceList', $combinedPriceList->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function findByPriceListIdAndProductIds(
        ShardManager $shardManager,
        $priceListId,
        array $productIds,
        $getTierPrices = true,
        $currency = null,
        $productUnitCode = null,
        array $orderBy = ['unit' => 'ASC', 'quantity' => 'ASC']
    ) {
        if (!$productIds) {
            return [];
        }

        $qb = $this->getFindByPriceListIdAndProductIdsQueryBuilder(
            $priceListId,
            $productIds,
            $getTierPrices,
            $currency,
            $productUnitCode,
            $orderBy
        );
        $qb
            ->addSelect('product', 'unitPrecisions', 'unit')
            ->leftJoin('price.product', 'product')
            ->leftJoin('product.unitPrecisions', 'unitPrecisions')
            ->leftJoin('unitPrecisions.unit', 'unit');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param integer $websiteId
     * @param Product[] $products
     * @param CombinedPriceList $configCpl
     * @return array
     */
    public function findMinByWebsiteForFilter($websiteId, array $products, $configCpl)
    {
        $qb = $this->getQbForMinimalPrices($websiteId, $products, $configCpl);
        $qb->select(
            'IDENTITY(mp.product) as product',
            'MIN(mp.value) as value',
            'mp.currency',
            'IDENTITY(mp.priceList) as cpl',
            'IDENTITY(mp.unit) as unit'
        );
        $qb->groupBy('mp.product, mp.priceList, mp.currency, mp.unit');

        return $qb->getQuery()->getArrayResult();
    }
    /**
     * @param integer $websiteId
     * @param Product[] $products
     * @param CombinedPriceList $configCpl
     * @return array
     */
    public function findMinByWebsiteForSort($websiteId, array $products, $configCpl)
    {
        $qb = $this->getQbForMinimalPrices($websiteId, $products, $configCpl);
        $qb->select(
            'IDENTITY(mp.product) as product',
            'MIN(mp.value) as value',
            'mp.currency',
            'IDENTITY(mp.priceList) as cpl'
        );
        $qb->groupBy('mp.product, mp.priceList, mp.currency');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * @param $websiteId
     * @param array $products
     * @param $configCpl
     * @return QueryBuilder
     */
    protected function getQbForMinimalPrices($websiteId, array $products, $configCpl)
    {
        $qb = $this->createQueryBuilder('mp');
        $qb->where('mp.product in (:products)')
            ->setParameter('products', $products);
        $expr = $qb->expr()->orX(
            $qb->expr()->orX(
                $qb->expr()->exists(
                    $this->getEntityManager()
                        ->createQueryBuilder()
                        ->from(CombinedPriceListToWebsite::class, 'cpl_w')
                        ->select('cpl_w.id')
                        ->where('cpl_w.website = :websiteId')
                        ->andWhere('cpl_w.priceList = mp.priceList')
                        ->getDQL()
                ),
                $qb->expr()->exists(
                    $this->getEntityManager()
                        ->createQueryBuilder()
                        ->from(CombinedPriceListToCustomer::class, 'cpl_a')
                        ->select('cpl_a.id')
                        ->where('cpl_a.website = :websiteId')
                        ->andWhere('cpl_a.priceList = mp.priceList')
                        ->getDQL()
                ),
                $qb->expr()->exists(
                    $this->getEntityManager()
                        ->createQueryBuilder()
                        ->from(CombinedPriceListToCustomerGroup::class, 'cpl_ag')
                        ->select('cpl_ag.id')
                        ->where('cpl_ag.website = :websiteId')
                        ->andWhere('cpl_ag.priceList = mp.priceList')
                        ->getDQL()
                )
            )
        );
        if ($configCpl) {
            $expr->add('mp.priceList = :conf_cpl');
            $qb->setParameter('conf_cpl', $configCpl);
        }
        $qb->andWhere($expr)
            ->setParameter('websiteId', $websiteId);

        return $qb;
    }
}
