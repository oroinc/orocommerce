<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\DashboardBundle\Helper\DateHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Bundle\OrderBundle\Provider\OrderStatusesProviderInterface;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadCancelledOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadConfigurableProductOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrganizations;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadSubOrders;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\OrdersCreatedAt\LoadOrdersCreatedAtRangeDuration1Day;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Tests\Functional\WebsiteTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderRepositoryTest extends WebTestCase
{
    use WebsiteTrait;

    private OrderRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrganizations::class,
            LoadOrders::class,
            LoadOrderLineItemData::class,
            LoadConfigurableProductOrderLineItemData::class,
            LoadCancelledOrders::class,
            LoadSubOrders::class,
            LoadOrdersCreatedAtRangeDuration1Day::class,
        ]);

        $this->repository = $this->getRepository();
    }

    public function testHasRecordsWithRemovingCurrencies(): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);
        self::assertNotNull($user);

        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganizations::ORGANIZATION_1);
        self::assertNotNull($organization);

        self::assertTrue($this->repository->hasRecordsWithRemovingCurrencies(['USD']));
        self::assertTrue($this->repository->hasRecordsWithRemovingCurrencies(['EUR']));
        self::assertFalse($this->repository->hasRecordsWithRemovingCurrencies(['UAH']));
        self::assertTrue($this->repository->hasRecordsWithRemovingCurrencies(['EUR'], $user->getOrganization()));
        self::assertFalse($this->repository->hasRecordsWithRemovingCurrencies(['USD'], $organization));
    }

    public function testGetOrderWithRelations(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);

        $orderWithRelations = $this->repository->getOrderWithRelations($order->getId());

        /** @var AbstractLazyCollection $lineItems */
        $lineItems = $orderWithRelations->getLineItems();

        /** @var AbstractLazyCollection $discounts */
        $discounts = $orderWithRelations->getDiscounts();

        self::assertTrue($lineItems->isInitialized());
        self::assertTrue($discounts->isInitialized());
    }

    public function testGetLatestOrderedProductsInfo(): void
    {
        $queryBuilder = $this->getRepository()->getLatestOrderedProductsInfo(
            [
                $this->getReference(LoadProductData::PRODUCT_1)->getId(),
                $this->getReference(LoadProductData::PRODUCT_5)->getId()
            ],
            $this->getDefaultWebsite()->getId(),
            [
                ExtendHelper::buildEnumOptionId(
                    Order::INTERNAL_STATUS_CODE,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                )
            ]
        );

        $result = $queryBuilder->getQuery()->getArrayResult();

        self::assertCount(2, $result);
        $this->assertContainsRecordWithProductAndCustomerUser(
            $result,
            LoadProductData::PRODUCT_1,
            LoadOrders::ACCOUNT_USER
        );
        $this->assertContainsRecordWithProductAndCustomerUser(
            $result,
            LoadProductData::PRODUCT_5,
            LoadOrders::ACCOUNT_USER
        );
    }

    private function assertContainsRecordWithProductAndCustomerUser(
        array $records,
        string $productReference,
        string $customerUserEmail
    ): void {
        $productId = $this->getReference($productReference)->getId();
        $customerUserId = $this->getCustomerUserByEmail($customerUserEmail)->getId();
        foreach ($records as $record) {
            // intentional non-strict comparison
            if ($productId == $record['product_id'] && $customerUserId == $record['customer_user_id']) {
                // just increase the asserts counter, as this should be counted as successfully performed assertion
                static::assertTrue(true);
                return;
            }
        }
        static::fail(sprintf(
            'Failed asserting that there is a record with product %s (product_id=%s)'
            . " and customer user %s (customer_user_id=%s):\n%s",
            $productReference,
            $productId,
            $customerUserEmail,
            $customerUserId,
            \var_export($records, true)
        ));
    }

    public function testGetLatestOrderedProductsInfoWhenConfigurableProductsGiven(): void
    {
        $queryBuilder = $this->getRepository()->getLatestOrderedProductsInfo(
            [
                $this->getReference(LoadProductData::PRODUCT_3)->getId(),
            ],
            $this->getDefaultWebsite()->getId(),
            [
                ExtendHelper::buildEnumOptionId(
                    Order::INTERNAL_STATUS_CODE,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                )
            ]
        );

        self::assertEmpty($queryBuilder->getQuery()->getArrayResult());
    }

    public function testGetLatestOrderedParentProductsInfo(): void
    {
        $queryBuilder = $this->getRepository()->getLatestOrderedParentProductsInfo(
            [
                $this->getReference(LoadProductData::PRODUCT_2)->getId(),
                $this->getReference(LoadProductData::PRODUCT_7)->getId()
            ],
            $this->getDefaultWebsite()->getId(),
            [
                ExtendHelper::buildEnumOptionId(
                    Order::INTERNAL_STATUS_CODE,
                    OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN
                )
            ]
        );

        $result = $queryBuilder->getQuery()->getArrayResult();

        $this->assertContainsRecordWithProductAndCustomerUser(
            $result,
            LoadProductData::PRODUCT_2,
            LoadOrders::ACCOUNT_USER
        );
        $this->assertContainsRecordWithProductAndCustomerUser(
            $result,
            LoadProductData::PRODUCT_7,
            LoadOrders::ACCOUNT_USER
        );
    }

    private function getCustomerUserByEmail(string $email): CustomerUser
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(CustomerUser::class)
            ->getRepository(CustomerUser::class)
            ->findOneBy(['email' => $email]);
    }

    private function getRepository(): OrderRepository
    {
        return self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Order::class)
            ->getRepository(Order::class);
    }

    public function testGetRelatedEntitiesCount(): void
    {
        $customerUser = $this->getReference(LoadOrders::ACCOUNT_USER);

        self::assertSame(7, $this->repository->getRelatedEntitiesCount($customerUser));
    }

    public function testGetRelatedEntitiesCountZero(): void
    {
        $customerUserWithoutRelatedEntities = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);

        self::assertSame(0, $this->repository->getRelatedEntitiesCount($customerUserWithoutRelatedEntities));
    }

    public function testResetCustomerUserForSomeEntities(): void
    {
        $customerUser = $this->getReference(LoadOrders::ACCOUNT_USER);
        $this->getRepository()->resetCustomerUser($customerUser, [
            $this->getReference(LoadOrders::ORDER_1),
            $this->getReference(LoadOrders::ORDER_2),
        ]);

        $orders = $this->getRepository()->findBy(['customerUser' => null]);

        self::assertCount(2, $orders);
    }

    public function testResetCustomerUser(): void
    {
        $customerUser = $this->getReference(LoadOrders::ACCOUNT_USER);
        $this->getRepository()->resetCustomerUser($customerUser);

        $orders = $this->getRepository()->findBy(['customerUser' => null]);

        self::assertCount(7, $orders);
    }

    /**
     * @dataProvider getSalesOrdersVolumeDataProvider
     */
    public function testGetSalesOrdersVolume(
        \DateTime $startDate,
        ?\DateTime $endDate,
        ?array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $amountType,
        string $currency,
        string $scaleType,
        array $expectedResults
    ): void {
        $salesOrdersVolume = $this->getRepository()->getSalesOrdersVolume(
            $startDate,
            $endDate,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $amountType,
            $currency,
            $scaleType
        );

        self::assertEquals($expectedResults, $salesOrdersVolume);
    }

    /**
     * @dataProvider getSalesOrdersVolumeDataProvider
     */
    public function testGetSalesOrdersVolumeQueryBuilder(
        \DateTime $startDate,
        ?\DateTime $endDate,
        ?array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $amountType,
        string $currency,
        string $scaleType,
        array $expectedResults
    ): void {
        $queryBuilder = $this->getRepository()->getSalesOrdersVolumeQueryBuilder(
            $startDate,
            $endDate,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $amountType,
            $currency,
            $scaleType
        );

        self::assertEquals($expectedResults, $queryBuilder->getQuery()->getResult());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getSalesOrdersVolumeDataProvider(): array
    {
        $today = new \DateTime('today', new \DateTimeZone('UTC'));
        $yearCreated = $today->format('Y');
        $monthCreated = $today->format('n');
        $dayCreated = $today->format('j');

        $minDate = new \DateTime(DateHelper::MIN_DATE, new \DateTimeZone('UTC'));
        $startDate = new \DateTime('today - 2 days', new \DateTimeZone('UTC'));
        $endDate = new \DateTime('today + 1 day', new \DateTimeZone('UTC'));

        $currency = 'USD';

        return [
            'all orders with total amount type' => [
                'startDate' => $minDate,
                'endDate' => null,
                'includedOrderStatuses' => null,
                'isIncludeSubOrders' => true,
                'amountType' => 'total',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '1234.0000',
                        'yearCreated' => '2023',
                        'monthCreated' => '1',
                        'dayCreated' => '4',
                    ],
                    [
                        'amount' => '9872.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'date range with total amount type' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => true,
                'amountType' => 'total',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '9872.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'without suborders with subtotal total type' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => false,
                'amountType' => 'total',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '8638.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'filtered by order status with total amount type' => [
                'startDate' => $minDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => [
                    ExtendHelper::buildEnumOptionId(
                        Order::INTERNAL_STATUS_CODE,
                        OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                    )
                ],
                'isIncludeSubOrders' => true,
                'amountType' => 'total',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '1234.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'empty results by unknown order status with total amount type' => [
                'startDate' => $minDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => ['unknown status'],
                'isIncludeSubOrders' => true,
                'amountType' => 'total',
                'currency' => $currency,
                'scaleType' => 'year',
                'expectedResults' => [],
            ],
            'empty results by date range with total amount type' => [
                'startDate' => new \DateTime(DateHelper::MIN_DATE . '- 5 days', new \DateTimeZone('UTC')),
                'endDate' => new \DateTime(DateHelper::MIN_DATE . '- 4 days', new \DateTimeZone('UTC')),
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => true,
                'amountType' => 'total',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [],
            ],
            'all orders with subtotal amount type' => [
                'startDate' => $minDate,
                'endDate' => null,
                'includedOrderStatuses' => null,
                'isIncludeSubOrders' => true,
                'amountType' => 'subtotal',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '789.0000',
                        'yearCreated' => '2023',
                        'monthCreated' => '1',
                        'dayCreated' => '4',
                    ],
                    [
                        'amount' => '6312.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'date range with subtotal amount type' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => true,
                'amountType' => 'subtotal',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '6312.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'without suborders with subtotal amount type' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => false,
                'amountType' => 'subtotal',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '5523.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'filtered by order status with subtotal amount type' => [
                'startDate' => $minDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => [
                    ExtendHelper::buildEnumOptionId(
                        Order::INTERNAL_STATUS_CODE,
                        OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                    )
                ],
                'isIncludeSubOrders' => true,
                'amountType' => 'subtotal',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '789.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'empty results by unknown order status with subtotal amount type' => [
                'startDate' => $minDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => ['unknown status'],
                'isIncludeSubOrders' => true,
                'amountType' => 'subtotal',
                'currency' => $currency,
                'scaleType' => 'year',
                'expectedResults' => [],
            ],
            'empty results by date range with subtotal amount type' => [
                'startDate' => new \DateTime(DateHelper::MIN_DATE . '- 5 days', new \DateTimeZone('UTC')),
                'endDate' => new \DateTime(DateHelper::MIN_DATE . '- 4 days', new \DateTimeZone('UTC')),
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => true,
                'amountType' => 'subtotal',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [],
            ],
            'all orders with subtotal_with_discounts amount type' => [
                'startDate' => $minDate,
                'endDate' => null,
                'includedOrderStatuses' => null,
                'isIncludeSubOrders' => true,
                'amountType' => 'subtotal_with_discounts',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '789.0000',
                        'yearCreated' => '2023',
                        'monthCreated' => '1',
                        'dayCreated' => '4',
                    ],
                    [
                        'amount' => '6312.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'date range with subtotal_with_discounts amount type' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => true,
                'amountType' => 'subtotal_with_discounts',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '6312.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'without suborders with subtotal_with_discounts amount type' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => false,
                'amountType' => 'subtotal_with_discounts',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '5523.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'filtered by order status with subtotal_with_discounts amount type' => [
                'startDate' => $minDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => [
                    ExtendHelper::buildEnumOptionId(
                        Order::INTERNAL_STATUS_CODE,
                        OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                    )
                ],
                'isIncludeSubOrders' => true,
                'amountType' => 'subtotal_with_discounts',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'amount' => '789.0000',
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ],
                ],
            ],
            'empty results by unknown order status with subtotal_with_discounts amount type' => [
                'startDate' => $minDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => ['unknown status'],
                'isIncludeSubOrders' => true,
                'amountType' => 'subtotal_with_discounts',
                'currency' => $currency,
                'scaleType' => 'year',
                'expectedResults' => [],
            ],
            'empty results by date range with subtotal_with_discounts amount type' => [
                'startDate' => new \DateTime(DateHelper::MIN_DATE . '- 5 days', new \DateTimeZone('UTC')),
                'endDate' => new \DateTime(DateHelper::MIN_DATE . '- 4 days', new \DateTimeZone('UTC')),
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => true,
                'amountType' => 'subtotal_with_discounts',
                'currency' => $currency,
                'scaleType' => 'day',
                'expectedResults' => [],
            ],
        ];
    }

    /**
     * @dataProvider getSalesOrdersNumberDataProvider
     */
    public function testGetSalesOrdersNumber(
        \DateTime $startDate,
        ?\DateTime $endDate,
        ?array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $scaleType,
        array $expectedResults
    ): void {
        $salesOrdersVolume = $this->getRepository()->getSalesOrdersNumber(
            $startDate,
            $endDate,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $scaleType
        );

        self::assertEquals($expectedResults, $salesOrdersVolume);
    }

    /**
     * @dataProvider getSalesOrdersNumberDataProvider
     */
    public function testGetSalesOrdersNumberQueryBuilder(
        \DateTime $startDate,
        ?\DateTime $endDate,
        ?array $includedOrderStatuses,
        bool $isIncludeSubOrders,
        string $scaleType,
        array $expectedResults
    ): void {
        $queryBuilder = $this->getRepository()->getSalesOrdersNumberQueryBuilder(
            $startDate,
            $endDate,
            $includedOrderStatuses,
            $isIncludeSubOrders,
            $scaleType
        );

        self::assertEquals($expectedResults, $queryBuilder->getQuery()->getResult());
    }

    public function testGetOrdersPurchaseVolume(): void
    {
        $result = $this->getRepository()
            ->getOrdersPurchaseVolume(
                $this->getDefaultWebsite()->getId(),
                'USD',
                'month',
                new \DateTime('-1 year', new \DateTimeZone('UTC')),
                ['cancelled']
            );

        $label = (new \DateTime('now'))->format('Y-m-01 00:00:00');

        self::assertSame([['label' => $label, 'value' => '7404.0000']], $result);
    }

    public function testGetOrdersPurchaseVolumeQueryBuilder(): void
    {
        $qb = $this->getRepository()
            ->getOrdersPurchaseVolumeQueryBuilder(
                $this->getDefaultWebsite()->getId(),
                'USD',
                'month',
                new \DateTime('-1 year', new \DateTimeZone('UTC')),
                ['cancelled']
            );

        $label = (new \DateTime('now'))->format('Y-m-01 00:00:00');

        self::assertSame([['label' => $label, 'value' => '7404.0000']], $qb->getQuery()->getResult());
    }

    public function testGetSumTotalOrders(): void
    {
        $result = $this->getRepository()
            ->getSumTotalOrders(
                $this->getDefaultWebsite()->getId(),
                'USD',
                ['cancelled']
            );

        self::assertSame('8638.0000', $result);
    }

    public function testGetSumTotalOrdersQueryBuilder(): void
    {
        $qb = $this->getRepository()
            ->getSumTotalOrdersQueryBuilder(
                $this->getDefaultWebsite()->getId(),
                'USD',
                ['cancelled']
            );

        self::assertSame([['total' => '8638.0000']], $qb->getQuery()->getResult());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getSalesOrdersNumberDataProvider(): array
    {
        $today = new \DateTime('today', new \DateTimeZone('UTC'));
        $yearCreated = $today->format('Y');
        $monthCreated = $today->format('n');
        $dayCreated = $today->format('j');

        $minDate = new \DateTime(DateHelper::MIN_DATE, new \DateTimeZone('UTC'));
        $startDate = new \DateTime('today - 2 days', new \DateTimeZone('UTC'));
        $endDate = new \DateTime('today + 1 day', new \DateTimeZone('UTC'));

        return [
            'all orders' => [
                'startDate' => $minDate,
                'endDate' => null,
                'includedOrderStatuses' => null,
                'isIncludeSubOrders' => true,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'number' => 1,
                        'yearCreated' => '2023',
                        'monthCreated' => '1',
                        'dayCreated' => '4',
                    ],
                    [
                        'number' => 9,
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ]
                ],
            ],
            'date range' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => true,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'number' => 9,
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ]
                ],
            ],
            'without suborders' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => false,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'number' => 8,
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ]
                ],
            ],
            'filtered by order status' => [
                'startDate' => $minDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => [
                    ExtendHelper::buildEnumOptionId(
                        Order::INTERNAL_STATUS_CODE,
                        OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED
                    )
                ],
                'isIncludeSubOrders' => true,
                'scaleType' => 'day',
                'expectedResults' => [
                    [
                        'number' => 1,
                        'yearCreated' => $yearCreated,
                        'monthCreated' => $monthCreated,
                        'dayCreated' => $dayCreated,
                    ]
                ],
            ],
            'empty results by unknown order status' => [
                'startDate' => $minDate,
                'endDate' => $endDate,
                'includedOrderStatuses' => ['unknown status'],
                'isIncludeSubOrders' => true,
                'scaleType' => 'day',
                'expectedResults' => [],
            ],
            'empty results by date range' => [
                'startDate' => new \DateTime(DateHelper::MIN_DATE . '- 5 days', new \DateTimeZone('UTC')),
                'endDate' => new \DateTime(DateHelper::MIN_DATE . '- 4 days', new \DateTimeZone('UTC')),
                'includedOrderStatuses' => self::getIncludedOrderStatus(),
                'isIncludeSubOrders' => true,
                'scaleType' => 'day',
                'expectedResults' => [],
            ],
        ];
    }

    protected static function getIncludedOrderStatus()
    {
        return array_map(
            fn ($optionId) => ExtendHelper::buildEnumOptionId(Order::INTERNAL_STATUS_CODE, $optionId),
            [
                OrderStatusesProviderInterface::INTERNAL_STATUS_OPEN,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CLOSED,
                OrderStatusesProviderInterface::INTERNAL_STATUS_CANCELLED,
            ]
        );
    }
}
