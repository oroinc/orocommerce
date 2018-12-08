<?php

namespace Oro\Bundle\OrderBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Provides purchase information for products.
 */
class LatestOrderedProductsInfoProvider
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var OrderStatusesProviderInterface
     */
    protected $statusesProvider;

    /**
     * LatestOrderedProductsInfoProvider constructor.
     * @param RegistryInterface $registry
     * @param OrderStatusesProviderInterface $availableOrderStatusesProvider
     */
    public function __construct(
        RegistryInterface $registry,
        OrderStatusesProviderInterface $availableOrderStatusesProvider
    ) {
        $this->registry = $registry;
        $this->statusesProvider = $availableOrderStatusesProvider;
    }

    /**
     * Returns information about who and when bought those products
     * [
     *      product id => [
     *          0 => [
     *              'customer_id' => customer user who bought,
     *              'created_at'  => order create \DateTime,
     *          ],
     *          ...
     *      ],
     *      ...
     * ]
     *
     * @param array $productIds
     * @param int   $websiteId
     *
     * @return array
     */
    public function getLatestOrderedProductsInfo(array $productIds, $websiteId)
    {
        $orderRepository = $this->getOrderRepository();
        $orderStatuses = $this->statusesProvider->getAvailableStatuses();

        $qb = $orderRepository->getLatestOrderedProductsInfo($productIds, $websiteId, $orderStatuses);
        $productsResult = $this->getResultFromQB($qb);

        $qb = $orderRepository->getLatestOrderedParentProductsInfo($productIds, $websiteId, $orderStatuses);
        $parentProductsResult = $this->getResultFromQB($qb);

        return array_replace($productsResult, $parentProductsResult);
    }

    /**
     * Executes query form query bulder and convert result to special format
     *
     * @param QueryBuilder $qb
     *
     * @return array
     */
    protected function getResultFromQB(QueryBuilder $qb)
    {
        $productIndexOrderInfo = [];
        $queryResult = $qb->getQuery()->getArrayResult();
        foreach ($queryResult as $item) {
            $productIndexOrderInfo[$item['product_id']][] = [
                'customer_user_id' => $item['customer_user_id'],
                'created_at'       => new \DateTime($item['created_at'])
            ];
        }

        unset($queryResult);

        return $productIndexOrderInfo;
    }

    /**
     * Returns Order repository from the registry
     *
     * @return OrderRepository
     */
    protected function getOrderRepository()
    {
        return $this->registry
            ->getManagerForClass('OroOrderBundle:Order')
            ->getRepository('OroOrderBundle:Order');
    }
}
