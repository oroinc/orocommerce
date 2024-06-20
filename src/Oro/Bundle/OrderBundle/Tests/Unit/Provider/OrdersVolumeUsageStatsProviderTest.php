<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Provider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Provider\OrdersVolumeUsageStatsProvider;
use Oro\Bundle\OrganizationBundle\Provider\OrganizationRestrictionProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrdersVolumeUsageStatsProviderTest extends TestCase
{
    private ObjectManager|MockObject $objectManager;
    private OrderRepository|MockObject $orderRepository;
    private OrganizationRestrictionProviderInterface|MockObject $organizationRestrictionProvider;
    private CurrencyProviderInterface|MockObject $currencyProvider;
    private NumberFormatter|MockObject $numberFormatter;
    private OrdersVolumeUsageStatsProvider $provider;

    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->organizationRestrictionProvider = $this->createMock(OrganizationRestrictionProviderInterface::class);
        $this->currencyProvider = $this->createMock(CurrencyProviderInterface::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $this->provider = new OrdersVolumeUsageStatsProvider(
            $this->objectManager,
            $this->organizationRestrictionProvider,
            $this->currencyProvider,
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

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(array $currencyList, bool $expectedResult): void
    {
        $this->currencyProvider->expects(self::once())
            ->method('getCurrencyList')
            ->willReturn($currencyList);

        self::assertEquals(
            $expectedResult,
            $this->provider->isApplicable()
        );
    }

    public function isApplicableDataProvider(): array
    {
        return [
            'one currency' => [
                'currencyList' => [
                    'USD'
                ],
                'expectedResult' => true,
            ],
            'multiple currencies' => [
                'currencyList' => [
                    'USD',
                    'EUR',
                ],
                'expectedResult' => false,
            ],
            'no currencies' => [
                'currencyList' => [],
                'expectedResult' => false,
            ],
        ];
    }

    /**
     * @dataProvider getValueDataProvider
     */
    public function testGetValue(array $repositoryResult, float $expectedFloat, string $expectedCurrency): void
    {
        $this->currencyProvider->expects(self::once())
            ->method('getCurrencyList')
            ->willReturn(['USD']);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->objectManager->expects(self::once())
            ->method('getRepository')
            ->with(Order::class)
            ->willReturn($this->orderRepository);

        $this->orderRepository->expects(self::once())
            ->method('getSalesOrdersVolumeQueryBuilder')
            ->with(
                $this->isInstanceOf(\DateTime::class),
                $this->isInstanceOf(\DateTime::class),
                [
                    OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_ARCHIVED,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_SHIPPED,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
                ],
                false,
                'total',
                'USD',
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
