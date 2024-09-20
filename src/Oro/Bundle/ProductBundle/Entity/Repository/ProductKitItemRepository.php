<?php

namespace Oro\Bundle\ProductBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Doctrine repository for {@see ProductKitItem} entity.
 */
class ProductKitItemRepository extends ServiceEntityRepository
{
    /**
     * Returns an array of SKUs of the product kits that reference specified $productUnitPrecision.
     *
     * @param ProductUnitPrecision $productUnitPrecision
     * @param int $limit
     *
     * @return string[]
     */
    public function findProductKitsSkuByUnitPrecision(
        ProductUnitPrecision $productUnitPrecision,
        int $limit = 10
    ): array {
        $qb = $this->createQueryBuilder('pki');

        return $qb
            ->select('pk.sku')
            ->innerJoin('pki.productKit', 'pk')
            ->innerJoin('pki.kitItemProducts', 'pkip')
            ->where($qb->expr()->eq('pkip.productUnitPrecision', ':product_unit_precision_id'))
            ->setParameter('product_unit_precision_id', $productUnitPrecision->getId(), Types::INTEGER)
            ->groupBy('pk.id')
            ->orderBy('pk.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);
    }

    /**
     * Returns an array of SKUs of the product kits that reference specified $product.
     *
     * @param Product $product
     * @param int $limit
     *
     * @return string[]
     */
    public function findProductKitsSkuByProduct(Product $product, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('pki');

        return $qb
            ->select('pk.sku')
            ->innerJoin('pki.productKit', 'pk')
            ->innerJoin('pki.kitItemProducts', 'pip')
            ->innerJoin(
                'pip.product',
                'p',
                Join::WITH,
                $qb->expr()->eq('p.id', ':product_id')
            )
            ->setParameter('product_id', $product->getId(), Types::INTEGER)
            ->groupBy('pk.id')
            ->setMaxResults($limit)
            ->orderBy('pk.id', 'DESC')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN);
    }

    /**
     * @param int $productKitId
     *
     * @return int Number of kit items related to the product kit with id $productKitId.
     */
    public function getKitItemsCount(int $productKitId): int
    {
        $qb = $this->createQueryBuilder('pki');

        return $qb
            ->select($qb->expr()->count('pki.id'))
            ->where($qb->expr()->eq('pki.productKit', ':product_kit_id'))
            ->setParameter('product_kit_id', $productKitId, Types::INTEGER)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }

    /**
     * Return example:
     *   [
     *        2902 => [
     *            0 =>
     *                [
     *                    'product' => 2902,
     *                    'product_kit_item' => 1447,
     *                    'status' =>
     *                        [
     *                            0 => 'enabled',
     *                            1 => 'disabled',
     *                        ],
     *                ],
     *           ....
     *        ],
     *        ....
     *    ]
     */
    public function getRequiredProductKitItemStatuses(int ...$productKitIds): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->from('oro_product', 'p1')
            ->select([
                'p1.id as product',
                'pki.id as product_kit_item',
                'string_agg(p2.status, \',\') as status',
            ]);
        $qb->where($qb->expr()->eq('p1.type', ':type'));
        $qb->setParameter('type', Product::TYPE_KIT, Types::STRING);
        $qb->andWhere($qb->expr()->in('p1.id', ':ids'));
        $qb->setParameter('ids', $productKitIds, Connection::PARAM_INT_ARRAY);
        $qb->innerJoin('p1', 'oro_product_kit_item', 'pki', 'pki.product_kit_id = p1.id AND pki.optional <> true');
        $qb->innerJoin('pki', 'oro_product_kit_item_product', 'pkip', 'pki.id = pkip.product_kit_item_id');
        $qb->innerJoin('pkip', 'oro_product', 'p2', 'pkip.product_id = p2.id');
        $qb->addGroupBy('p1.id', 'pki.id');
        $qb->orderBy('p1.id');

        $data = $qb->execute()->fetchAllAssociative();

        $result = [];

        foreach ($productKitIds as $productKitId) {
            $result[$productKitId] = array_filter($data, static fn (array $item) => $item['product'] == $productKitId);
            foreach ($result[$productKitId] as $k => $item) {
                $result[$productKitId][$k]['status'] = explode(',', $result[$productKitId][$k]['status']);
            }
        }

        return $result;
    }

    /**
     * Return example:
     *   [
     *        2902 => [
     *            0 =>
     *                [
     *                    'product' => 2902,
     *                    'product_kit_item' => 1447,
     *                    'status' =>
     *                        [
     *                            0 => 'in_stock',
     *                            1 => 'out_of_stock',
     *                            2 => 'discontinued',
     *                        ],
     *                ],
     *           ....
     *        ],
     *        ....
     *    ]
     */
    public function getRequiredProductKitItemInventoryStatuses(int ...$productKitIds): array
    {
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb->from('oro_product', 'p1')
            ->select([
                'p1.id as product',
                'pki.id as product_kit_item',
                'string_agg(pis.id, \',\') as status',
            ]);
        $qb->where($qb->expr()->eq('p1.type', ':type'));
        $qb->setParameter('type', Product::TYPE_KIT, Types::STRING);
        $qb->andWhere($qb->expr()->in('p1.id', ':ids'));
        $qb->setParameter('ids', $productKitIds, Connection::PARAM_INT_ARRAY);
        $qb->innerJoin('p1', 'oro_product_kit_item', 'pki', 'pki.product_kit_id = p1.id AND pki.optional <> true');
        $qb->innerJoin('pki', 'oro_product_kit_item_product', 'pkip', 'pki.id = pkip.product_kit_item_id');
        $qb->innerJoin('pkip', 'oro_product', 'p2', 'pkip.product_id = p2.id');
        $qb->innerJoin('p2', 'oro_enum_option', 'pis', "p2.serialized_data::jsonb->>'inventory_status' = pis.id");
        $qb->addGroupBy('p1.id', 'pki.id');
        $qb->orderBy('p1.id');

        $data = $qb->execute()->fetchAllAssociative();

        $result = [];

        foreach ($productKitIds as $productKitId) {
            $result[$productKitId] = array_filter($data, static fn (array $item) => $item['product'] == $productKitId);
            foreach ($result[$productKitId] as $k => $item) {
                $result[$productKitId][$k]['status'] = explode(',', $result[$productKitId][$k]['status']);
            }
        }

        return $result;
    }

    /**
     * Returns ProductKitItem by ID and Product organization.
     */
    public function getProductKitItemByIdAndOrganization(int $kitItemId, int $organizationId): ?ProductKitItem
    {
        $qb = $this->createQueryBuilder('pki');

        return $qb
            ->innerJoin('pki.productKit', 'pk')
            ->where($qb->expr()->eq('pki.id', ':product_kit_item_id'))
            ->andWhere($qb->expr()->eq('pk.organization', ':organization_id'))
            ->setParameter('product_kit_item_id', $kitItemId, Types::INTEGER)
            ->setParameter('organization_id', $organizationId, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
