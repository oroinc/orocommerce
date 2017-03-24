<?php

namespace Oro\Bundle\PricingBundle\Entity\Repository;

use Doctrine\ORM\Id\UuidGenerator;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ORM\InsertFromSelectShardQueryExecutor;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductPriceRepository extends BaseProductPriceRepository
{
    const BUFFER_SIZE = 500;

    /**
     * @var UuidGenerator
     */
    protected $uuidGenerator;

    /**
     * @param ShardManager $shardManager
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function deleteGeneratedPrices(
        ShardManager $shardManager,
        PriceList $priceList,
        Product $product = null
    ) {
        $qb = $this->getDeleteQbByPriceList($priceList, $product);
        $query = $qb->andWhere($qb->expr()->isNotNull('productPrice.priceRule'))->getQuery();

        $query->useQueryCache(false);
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        $query->execute();
    }

    /**
     * @param ShardManager $shardManager
     * @param PriceList $priceList
     */
    public function deleteInvalidPrices(ShardManager $shardManager, PriceList $priceList)
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

        $query->useQueryCache(false);
        $query->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $shardManager);
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

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
     * @param BasePriceList $sourcePriceList
     * @param BasePriceList $targetPriceList
     * @param InsertFromSelectShardQueryExecutor $insertQueryExecutor
     */
    public function copyPrices(
        BasePriceList $sourcePriceList,
        BasePriceList $targetPriceList,
        InsertFromSelectShardQueryExecutor $insertQueryExecutor
    ) {
        $qb = $this->createQBForCopy($sourcePriceList, $targetPriceList);
        $qb->addSelect('UUID()');

        $fields = [
            'product',
            'unit',
            'priceList',
            'productSku',
            'quantity',
            'value',
            'currency',
            'id',
        ];

        $insertQueryExecutor->execute($this->getClassName(), $fields, $qb);
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
     * @param ShardManager $shardManager
     * @param BaseProductPrice $price
     */
    public function remove(ShardManager $shardManager, BaseProductPrice $price)
    {
        $tableName = $shardManager->getShardName($this->_entityName, ['priceList' => $price->getPriceList()]);
        $connection = $this->_em->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->delete($tableName)
            ->where('id = :id')
            ->setParameter('id', $price->getId())
            ->execute();
    }

    /**
     * @param ShardManager $shardManager
     * @param BaseProductPrice|ProductPrice $price
     */
    public function save(ShardManager $shardManager, BaseProductPrice $price)
    {
        $connection = $this->_em->getConnection();
        $qb = $connection->createQueryBuilder();
        $tableName = $shardManager->getShardName($this->_entityName, ['priceList' => $price->getPriceList()]);
        $columns = [
            'price_rule_id' => ':price_rule_id',
            'unit_code' => ':unit_code',
            'product_id' => ':product_id',
            'price_list_id' => ':price_list_id',
            'product_sku' => ':product_sku',
            'quantity' => ':quantity',
            'value' => ':value',
            'currency' => ':currency',
        ];
        if ($price->getId()) {
            $qb->update($tableName, 'price');
            foreach ($columns as $column => $placeholder) {
                $qb->set($column, $placeholder);
            }
            $qb->where('id = :id')
                ->setParameter('id', $price->getId());
        } else {
            $id = $this->getGenerator()->generate($this->_em, null);
            $columns['id'] = ':id';
            $qb->setParameter('id', $id);
            $qb->insert($tableName)
                ->values($columns);
            $price->setId($id);
        }
        $qb
            ->setParameter('price_rule_id', $price->getPriceRule() ? $price->getPriceRule()->getId(): null)
            ->setParameter('unit_code', $price->getProductUnitCode())
            ->setParameter('product_id', $price->getProduct()->getId())
            ->setParameter('price_list_id', $price->getPriceList()->getId())
            ->setParameter('product_sku', $price->getProductSku())
            ->setParameter('quantity', $price->getQuantity())
            ->setParameter('value', $price->getPrice()->getValue())
            ->setParameter('currency', $price->getPrice()->getCurrency());
        $qb->execute();
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

    /**
     * @return UuidGenerator
     */
    protected function getGenerator()
    {
        if (!$this->uuidGenerator) {
            $this->uuidGenerator = new UuidGenerator();
        }

        return $this->uuidGenerator;
    }
}
