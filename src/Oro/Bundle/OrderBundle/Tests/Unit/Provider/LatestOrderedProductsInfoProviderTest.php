<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\Provider\LatestOrderedProductsInfoProvider;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\RegistryInterface;

class LatestOrderedProductsInfoProviderTest extends \PHPUnit\Framework\TestCase
{
    private const WEBSITE_ID = 1;
    private const PRODUCT_IDS = [1, 5];

    /**
     * @var RegistryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var OrderStatusesProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $availableOrderStatusesProvider;

    /**
     * @var LatestOrderedProductsInfoProvider
     */
    private $latestOrderedProductsInfoProvider;

    protected function setUp()
    {
        $this->registry = $this->createMock(RegistryInterface::class);
        $this->availableOrderStatusesProvider = $this->createMock(OrderStatusesProviderInterface::class);

        $this->latestOrderedProductsInfoProvider = new LatestOrderedProductsInfoProvider(
            $this->registry,
            $this->availableOrderStatusesProvider
        );
    }

    public function testGetLatestOrderedProductsInfo(): void
    {
        $orderRepository = $this->configureRegistry();

        $statuses = [
            OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
        ];

        $productsQueryBuilder = $this->configureQueryBuilder([
            [
                'product_id' => 1,
                'customer_user_id' => 101,
                'created_at' => '2018-10-11'
            ],
            [
                'product_id' => 1,
                'customer_user_id' => 201,
                'created_at' => '2018-12-11'
            ],
            [
                'product_id' => 2,
                'customer_user_id' => 102,
                'created_at' => '2018-10-12'
            ],
        ]);

        $orderRepository
            ->expects(self::once())
            ->method('getLatestOrderedProductsInfo')
            ->with(self::PRODUCT_IDS, self::WEBSITE_ID, $statuses)
            ->willReturn($productsQueryBuilder);

        $parentProductsQueryBuilder = $this->configureQueryBuilder([
            [
                'product_id' => 3,
                'customer_user_id' => 103,
                'created_at' => '2018-10-13'
            ],
        ]);
        $orderRepository
            ->expects(self::once())
            ->method('getLatestOrderedParentProductsInfo')
            ->with(self::PRODUCT_IDS, self::WEBSITE_ID, $statuses)
            ->willReturn($parentProductsQueryBuilder);

        $this->availableOrderStatusesProvider
            ->expects(self::once())
            ->method('getAvailableStatuses')
            ->willReturn($statuses);

        $expectedResult = [
            1 => [
                [
                    'customer_user_id' => 101,
                    'created_at' => new \DateTime('2018-10-11')
                ],
                [
                    'customer_user_id' => 201,
                    'created_at' => new \DateTime('2018-12-11')
                ]
            ],
            2 => [
                [
                    'customer_user_id' => 102,
                    'created_at' => new \DateTime('2018-10-12')
                ]
            ],
            3 => [
                [
                    'customer_user_id' => 103,
                    'created_at' => new \DateTime('2018-10-13')
                ]
            ]
        ];

        self::assertEquals(
            $expectedResult,
            $this->latestOrderedProductsInfoProvider->getLatestOrderedProductsInfo(self::PRODUCT_IDS, self::WEBSITE_ID)
        );
    }

    /**
     * @param array $items
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    private function configureQueryBuilder(array $items): MockObject
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($items);

        $queryBuilder
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        return $queryBuilder;
    }

    /**
     * @return MockObject
     */
    private function configureRegistry(): MockObject
    {
        $repository = $this->createMock(OrderRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager
            ->expects(self::atLeastOnce())
            ->method('getRepository')
            ->with('OroOrderBundle:Order')
            ->willReturn($repository);

        $this->registry
            ->expects(self::atLeastOnce())
            ->method('getManagerForClass')
            ->with('OroOrderBundle:Order')
            ->willReturn($manager);

        return $repository;
    }
}
