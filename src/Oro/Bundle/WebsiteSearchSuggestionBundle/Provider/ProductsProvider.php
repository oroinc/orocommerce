<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;

/**
 * This class provides the method for getting available products.
 */
class ProductsProvider
{
    public function __construct(
        private ManagerRegistry $managerRegistry,
        private ConfigManager $configManager,
    ) {
    }

    public function getListOfProductIdAndOrganizationId(array $filterIds = []): \Iterator
    {
        $qb = $this->getAvailableProductsQB($filterIds)->select([
            'o.id as organizationId',
            'p.id'
        ]);

        return new BufferedIdentityQueryResultIterator($qb);
    }

    /**
     * @return array<int, array{
     *     names: array<int, ProductName>,
     *     sku: string
     * }>
     * [
     *  2 => [
     *      names => [new ProductName(), new ProductName(), ...],
     *      sku => 'product sku value'
     *  ]
     * ]
     */
    public function getProductsSkuAndNames(array $productIds): array
    {
        $result = [];

        $skuQueryResult = $this->getProductsSku($productIds);

        $namesQueryResult = $this->getProductNames(array_column($skuQueryResult, 'id'));

        foreach ($skuQueryResult as $item) {
            $result[$item['id']] = ['sku' => $item['sku']];
        }

        foreach ($namesQueryResult as $productName) {
            $result[$productName->getProduct()->getId()]['names'][] = $productName;
        }

        return $result;
    }

    private function getProductsSku(array $productIds): array
    {
        return $this->getAvailableProductsQB($productIds)->select(['p.id', 'p.sku'])->getQuery()->getArrayResult();
    }

    private function getProductNames(array $productIds): array
    {
        $productsQuery = $this->managerRegistry
            ->getRepository(Product::class)
            ->getProductsQueryBuilder($productIds);

        /**
         * @var QueryBuilder $qb
         */
        $qb = $this->managerRegistry->getRepository(ProductName::class)->createQueryBuilder('pn');

        $query = $qb
            ->where($qb->expr()->in('pn.product', $productsQuery->getDQL()))
            ->getQuery();

        foreach ($productsQuery->getParameters() as $parameter) {
            $query->setParameter($parameter->getName(), $parameter->getValue());
        }

        return $query->getResult();
    }

    private function getAvailableProductsQB(array $ids = []): QueryBuilder
    {
        return $this->managerRegistry
            ->getRepository(Product::class)
            ->getProductsQueryBuilder($ids)
            ->innerJoin(
                EnumOption::class,
                'inv',
                Join::WITH,
                "JSON_EXTRACT(p.serialized_data, 'inventory_status') = inv.id"
            )
            ->innerJoin('p.organization', 'o')
            ->andWhere('p.status = :status')
            ->setParameter('status', Product::STATUS_ENABLED)
            ->andWhere('inv.id IN (:inventoryStatuses)')
            ->setParameter('inventoryStatuses', $this->getVisibleInventoryStatuses());
    }

    private function getVisibleInventoryStatuses(): array
    {
        return $this->configManager->get('oro_product.general_frontend_product_visibility');
    }
}
