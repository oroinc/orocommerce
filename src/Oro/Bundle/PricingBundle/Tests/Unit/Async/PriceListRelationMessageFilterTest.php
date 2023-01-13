<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\PricingBundle\Async\PriceListRelationMessageFilter;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Async\Topic\RebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceListRelationMessageFilterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var PriceListRelationMessageFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->filter = new PriceListRelationMessageFilter($this->doctrine);
    }

    /**
     * @param EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository
     * @param int[]                                                     $websiteIds
     * @param int[]                                                     $customerIds
     * @param PriceListCustomerFallback[]                               $fallback
     */
    private function expectGetPreserveCustomers(
        EntityRepository $repository,
        array $websiteIds,
        array $customerIds,
        array $fallback
    ) {
        $repository->expects(self::once())
            ->method('findBy')
            ->with([
                'website'  => $websiteIds,
                'customer' => $customerIds,
                'fallback' => PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY
            ])
            ->willReturn($fallback);
    }

    /**
     * @param EntityRepository|\PHPUnit\Framework\MockObject\MockObject $repository
     * @param int[]                                                     $websiteIds
     * @param int[]                                                     $customerGroupIds
     * @param PriceListCustomerGroupFallback[]                          $fallback
     */
    private function expectGetPreserveCustomerGroups(
        EntityRepository $repository,
        array $websiteIds,
        array $customerGroupIds,
        array $fallback
    ) {
        $repository->expects(self::once())
            ->method('findBy')
            ->with([
                'website'       => $websiteIds,
                'customerGroup' => $customerGroupIds,
                'fallback'      => PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY
            ])
            ->willReturn($fallback);
    }

    private function getPriceListCustomerFallback(
        int $websiteId,
        int $customerId
    ): PriceListCustomerFallback {
        $fallback = new PriceListCustomerFallback();
        $fallback->setWebsite($this->getEntity(Website::class, ['id' => $websiteId]));
        $fallback->setCustomer($this->getEntity(Customer::class, ['id' => $customerId]));

        return $fallback;
    }

    private function getPriceListCustomerGroupFallback(
        int $websiteId,
        int $customerGroupId
    ): PriceListCustomerGroupFallback {
        $fallback = new PriceListCustomerGroupFallback();
        $fallback->setWebsite($this->getEntity(Website::class, ['id' => $websiteId]));
        $fallback->setCustomerGroup($this->getEntity(CustomerGroup::class, ['id' => $customerGroupId]));

        return $fallback;
    }

    public function testFullRebuild()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['force' => true]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 2]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 3, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['force' => true]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 4, 'customerGroup' => 12]);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                6 => [MassRebuildCombinedPriceListsTopic::getName(), ['assignments' => [['force' => true]]]]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedForWebsite()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 2]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                3 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1],
                            ['website' => 2]
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedForCustomerGroup()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 12]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                3 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1, 'customerGroup' => 11],
                            ['website' => 1, 'customerGroup' => 12]
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedForCustomer()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 101]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 102]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 101]);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                3 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1, 'customer' => 101],
                            ['website' => 1, 'customer' => 102],
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedAndRedundantForCustomerGroup()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 2]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 12]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);

        $customerGroupFallbackRepository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceListCustomerGroupFallback::class)
            ->willReturn($customerGroupFallbackRepository);
        $this->expectGetPreserveCustomerGroups($customerGroupFallbackRepository, [1], [11, 12], []);

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                6 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1],
                            ['website' => 2],
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedAndRedundantForCustomerGroupWithPreserveCustomerGroups()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 2]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 12]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);

        $customerGroupFallbackRepository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceListCustomerGroupFallback::class)
            ->willReturn($customerGroupFallbackRepository);
        $this->expectGetPreserveCustomerGroups(
            $customerGroupFallbackRepository,
            [1],
            [11, 12],
            [$this->getPriceListCustomerGroupFallback(1, 12)]
        );

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                6 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1],
                            ['website' => 2],
                            ['website' => 1, 'customerGroup' => 12]
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedAndRedundantForCustomer()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 2]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 101]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 102]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 101]);

        $customerFallbackRepository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceListCustomerFallback::class)
            ->willReturn($customerFallbackRepository);
        $this->expectGetPreserveCustomers($customerFallbackRepository, [1], [101, 102], []);

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                6 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1],
                            ['website' => 2],
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedAndRedundantForCustomerWithPreserveCustomers()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 2]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 101]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 102]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 101]);

        $customerFallbackRepository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceListCustomerFallback::class)
            ->willReturn($customerFallbackRepository);
        $this->expectGetPreserveCustomers(
            $customerFallbackRepository,
            [1],
            [101, 102],
            [$this->getPriceListCustomerFallback(1, 102)]
        );

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                6 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1],
                            ['website' => 2],
                            ['website' => 1, 'customer' => 102]
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedAndRedundantForCustomerWhenCorrespondingCustomerGroupMessagesExist()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 12]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);

        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 11, 'customer' => 101]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 12, 'customer' => 102]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 11, 'customer' => 101]
        );

        $customerFallbackRepository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceListCustomerFallback::class)
            ->willReturn($customerFallbackRepository);
        $this->expectGetPreserveCustomers($customerFallbackRepository, [1], [101, 102], []);

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                6 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1, 'customerGroup' => 11],
                            ['website' => 1, 'customerGroup' => 12],
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedAndRedundantForCustomerWhenCorrespondingCustomerGroupMessagesExistWithPreserve()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 12]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);

        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 11, 'customer' => 101]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 12, 'customer' => 102]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 11, 'customer' => 101]
        );

        $customerFallbackRepository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(PriceListCustomerFallback::class)
            ->willReturn($customerFallbackRepository);
        $this->expectGetPreserveCustomers(
            $customerFallbackRepository,
            [1],
            [101, 102],
            [$this->getPriceListCustomerFallback(1, 102)]
        );

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                6 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1, 'customerGroup' => 11],
                            ['website' => 1, 'customerGroup' => 12],
                            ['website' => 1, 'customerGroup' => 12, 'customer' => 102],
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedAndRedundant()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 2]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 12]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 13]);

        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 11, 'customer' => 101]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 11, 'customer' => 102]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 11, 'customer' => 101]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 12, 'customer' => 103]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 13, 'customer' => 104]
        );

        $customerGroupFallbackRepository = $this->createMock(EntityRepository::class);
        $customerFallbackRepository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [PriceListCustomerGroupFallback::class, null, $customerGroupFallbackRepository],
                [PriceListCustomerFallback::class, null, $customerFallbackRepository]
            ]);
        $this->expectGetPreserveCustomerGroups($customerGroupFallbackRepository, [1], [11, 12, 13], []);
        $this->expectGetPreserveCustomers($customerFallbackRepository, [1], [101, 102, 103, 104], []);

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                12 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1],
                            ['website' => 2],
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedAndRedundantWithPreserveCustomerGroupsAndCustomers()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 2]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 12]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 13]);

        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 11, 'customer' => 101]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 11, 'customer' => 102]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 11, 'customer' => 101]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 12, 'customer' => 103]
        );
        $buffer->addMessage(
            RebuildCombinedPriceListsTopic::getName(),
            ['website' => 1, 'customerGroup' => 13, 'customer' => 104]
        );

        $customerGroupFallbackRepository = $this->createMock(EntityRepository::class);
        $customerFallbackRepository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [PriceListCustomerGroupFallback::class, null, $customerGroupFallbackRepository],
                [PriceListCustomerFallback::class, null, $customerFallbackRepository]
            ]);
        $this->expectGetPreserveCustomerGroups(
            $customerGroupFallbackRepository,
            [1],
            [11, 12, 13],
            [$this->getPriceListCustomerGroupFallback(1, 12)]
        );
        $this->expectGetPreserveCustomers(
            $customerFallbackRepository,
            [1],
            [101, 102, 103, 104],
            [$this->getPriceListCustomerFallback(1, 102)]
        );

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                12 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1],
                            ['website' => 2],
                            ['website' => 1, 'customerGroup' => 12],
                            ['website' => 1, 'customerGroup' => 11, 'customer' => 102]
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedAndRedundantWithCustomerMessagesWithoutCustomerGroup()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 2]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 12]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 101]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 102]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 101]);

        $customerGroupFallbackRepository = $this->createMock(EntityRepository::class);
        $customerFallbackRepository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [PriceListCustomerGroupFallback::class, null, $customerGroupFallbackRepository],
                [PriceListCustomerFallback::class, null, $customerFallbackRepository]
            ]);
        $this->expectGetPreserveCustomerGroups($customerGroupFallbackRepository, [1], [11, 12], []);
        $this->expectGetPreserveCustomers($customerFallbackRepository, [1], [101, 102], []);

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                9 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1],
                            ['website' => 2],
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }

    public function testDuplicatedAndRedundantWithCustomerMessagesWithoutCustomerGroupWithPreserve()
    {
        $buffer = new MessageBuffer();

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 2]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 12]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customerGroup' => 11]);

        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 101]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 102]);
        $buffer->addMessage(RebuildCombinedPriceListsTopic::getName(), ['website' => 1, 'customer' => 101]);

        $customerGroupFallbackRepository = $this->createMock(EntityRepository::class);
        $customerFallbackRepository = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [PriceListCustomerGroupFallback::class, null, $customerGroupFallbackRepository],
                [PriceListCustomerFallback::class, null, $customerFallbackRepository]
            ]);
        $this->expectGetPreserveCustomerGroups(
            $customerGroupFallbackRepository,
            [1],
            [11, 12],
            [$this->getPriceListCustomerGroupFallback(1, 12)]
        );
        $this->expectGetPreserveCustomers(
            $customerFallbackRepository,
            [1],
            [101, 102],
            [$this->getPriceListCustomerFallback(1, 102)]
        );

        $this->filter->apply($buffer);

        $this->assertSame(
            [
                9 => [
                    MassRebuildCombinedPriceListsTopic::getName(),
                    [
                        'assignments' => [
                            ['website' => 1],
                            ['website' => 2],
                            ['website' => 1, 'customerGroup' => 12],
                            ['website' => 1, 'customer' => 102]
                        ]
                    ]
                ]
            ],
            $buffer->getMessages()
        );
    }
}
