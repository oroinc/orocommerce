<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\Provider\OrdersNumberUsageStatsProvider;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrdersNumberUsageStatsProviderTest extends TestCase
{
    private ManagerRegistry|MockObject $doctrine;
    private OrderRepository|MockObject $orderRepository;
    private OrganizationRestrictionProviderInterface|MockObject $organizationRestrictionProvider;
    private OrdersNumberUsageStatsProvider $provider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->organizationRestrictionProvider = $this->createMock(OrganizationRestrictionProviderInterface::class);

        $this->provider = new OrdersNumberUsageStatsProvider(
            $this->doctrine,
            $this->organizationRestrictionProvider
        );
    }

    public function testIsApplicable(): void
    {
        self::assertTrue($this->provider->isApplicable());
    }

    public function testGetTitle(): void
    {
        self::assertEquals(
            'oro.order.usage_stats.orders_number.label',
            $this->provider->getTitle()
        );
    }

    public function testGetTooltip(): void
    {
        self::assertEquals(
            'oro.order.usage_stats.orders_number.tooltip',
            $this->provider->getTooltip()
        );
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue(array $repositoryResult, string $expectedResult): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->orderRepository);

        $this->orderRepository->expects(self::once())
            ->method('getSalesOrdersNumberQueryBuilder')
            ->with(
                $this->isInstanceOf(\DateTime::class),
                null,
                null,
                false,
                'year'
            )
            ->willReturn($queryBuilder);

        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getResult')
            ->willReturn($repositoryResult);

        $this->organizationRestrictionProvider->expects(self::once())
            ->method('applyOrganizationRestrictions')
            ->with($queryBuilder);

        self::assertSame(
            $expectedResult,
            $this->provider->getValue()
        );
    }

    public function getValueDataProvider(): array
    {
        return [
            'normal result' => [
                'repositoryResult' => [
                    [
                        'number' => 42,
                        'yearCreated' => 2014,
                    ],
                    [
                        'number' => 31,
                        'yearCreated' => 2022,
                    ],
                ],
                'expectedResult' => '42',
            ],
            'no number in result' => [
                'repositoryResult' => [
                    [
                        'yearCreated' => 2014,
                    ],
                    [
                        'number' => 31,
                        'yearCreated' => 2022,
                    ],
                ],
                'expectedResult' => '0',
            ],
            'empty result' => [
                'repositoryResult' => [],
                'expectedResult' => '0',
            ],
        ];
    }
}
