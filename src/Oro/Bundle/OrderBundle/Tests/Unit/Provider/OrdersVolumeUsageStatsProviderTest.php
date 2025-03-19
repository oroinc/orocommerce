<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\Provider\OrdersVolumeUsageStatsProvider;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrdersVolumeUsageStatsProviderTest extends TestCase
{
    private ManagerRegistry|MockObject $doctrine;
    private OrderRepository|MockObject $orderRepository;
    private OrganizationRestrictionProviderInterface|MockObject $organizationRestrictionProvider;
    private NumberFormatter|MockObject $numberFormatter;
    private OrdersVolumeUsageStatsProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->organizationRestrictionProvider = $this->createMock(OrganizationRestrictionProviderInterface::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $this->provider = new OrdersVolumeUsageStatsProvider(
            $this->doctrine,
            $this->organizationRestrictionProvider,
            $this->numberFormatter
        );
    }

    public function testGetTitle(): void
    {
        self::assertEquals(
            'oro.order.usage_stats.orders_volume.label',
            $this->provider->getTitle()
        );
    }

    public function testGetTooltip(): void
    {
        self::assertEquals(
            'oro.order.usage_stats.orders_volume.tooltip',
            $this->provider->getTooltip()
        );
    }

    public function testIsApplicable(): void
    {
        self::assertEquals(true, $this->provider->isApplicable());
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue(array $repositoryResult, float $expectedFloat, string $expectedCurrency): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->orderRepository);

        $this->orderRepository->expects(self::once())
            ->method('getSalesOrdersVolumeQueryBuilder')
            ->with(
                $this->isInstanceOf(\DateTime::class),
                null,
                null,
                false,
                'total',
                null,
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

        $this->numberFormatter->expects(self::once())
            ->method('formatCurrency')
            ->with($expectedFloat)
            ->willReturn($expectedCurrency);

        self::assertSame(
            $expectedCurrency,
            $this->provider->getValue()
        );
    }

    public function getValueDataProvider(): array
    {
        return [
            'normal result' => [
                'repositoryResult' => [
                    [
                        'amount' => 42.250,
                        'yearCreated' => 2014,
                    ],
                    [
                        'amount' => 0.0,
                        'yearCreated' => 2022,
                    ],
                ],
                'expectedFloat' => 42.25,
                'expectedCurrency' => '$42.25',
            ],
            'no amount in result' => [
                'repositoryResult' => [
                    [
                        'yearCreated' => 2014,
                    ],
                    [
                        'amount' => 31.0,
                        'yearCreated' => 2022,
                    ],
                ],
                'expectedFloat' => 0.0,
                'expectedCurrency' => '$0',
            ],
            'empty result' => [
                'repositoryResult' => [],
                'expectedFloat' => 0.0,
                'expectedCurrency' => '$0',
            ],
        ];
    }
}
