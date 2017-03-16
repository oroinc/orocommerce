<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;

class ProductPriceRepository extends BaseProductPriceRepository
{
    const BUFFER_SIZE = 500;

    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function deleteGeneratedPrices(
        QueryHintResolverInterface $hintResolver,
        PriceList $priceList,
        Product $product = null
    ) {
        $qb = $this->getDeleteQbByPriceList($priceList, $product);
        $query = $qb->andWhere($qb->expr()->isNotNull('productPrice.priceRule'))->getQuery();
        $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);
        $query->execute();
    }

    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param PriceList $priceList
     */
    public function deleteInvalidPrices(QueryHintResolverInterface $hintResolver, PriceList $priceList)
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
        $query = $qb->getQuery();
        $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);
        $iterator = new BufferedIdentityQueryResultIterator($query);
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

    /**
     * {@inheritdoc}
     */
    public function getProductIdsByPriceLists(array $priceLists)
    {
        $this->_em->createQueryBuilder();
        $qb = $this->_em->createQueryBuilder();

        $result = $qb->select('IDENTITY(productToPriceList.product) as productId')
            ->from(PriceListToProduct::class, 'productToPriceList')
            ->where('productToPriceList.priceList IN (:priceLists)')
            ->setParameter('priceLists', $priceLists)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }

    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param BaseProductPrice $price
     */
    public function remove(QueryHintResolverInterface $hintResolver, BaseProductPrice $price)
    {
        //TODO: BB-8042 add test
        $qb = $this->_em->createQueryBuilder();
        $qb->delete($this->_entityName, 'price');
        $qb->where('price = :price')
            ->setParameter('price', $price);
        $query = $qb->getQuery();
        $hintResolver->resolveHints($query, [PriceShardWalker::HINT_PRICE_SHARD]);
        $query->execute();
    }

    /**
     * @param QueryHintResolverInterface $hintResolver
     * @param BaseProductPrice $price
     */
    public function persist(QueryHintResolverInterface $hintResolver, BaseProductPrice $price)
    {
        //TODO: BB-8042
        $this->_em->persist($price);
        $this->_em->flush($price);
    }

    /**
     * {@inheritdoc}
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        throw new \LogicException('Method locked because of sharded tables');
    }
//    /**
//     * {@inheritdoc}
//     */
//    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
//    {
//
//        throw new \LogicException('Method locked because of sharded tables');
//    }

//    /**
//     * {@inheritdoc}
//     */
//    public function findAll()
//    {
//        throw new \LogicException('Method locked because of sharded tables');
//    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        throw new \LogicException('Method locked because of sharded tables');
    }
}
