<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Model;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\MassRebuildCombinedPriceListsTopic;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelationsForTriggers;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @dbIsolationPerTest
 */
class CombinedPriceListRelationTriggerHandlerTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var PriceListRelationTriggerHandler */
    private $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadPriceListRelationsForTriggers::class]);
        $this->enableMessageBuffering();

        $this->handler = self::getContainer()->get('oro_pricing.price_list_relation_trigger_handler.combined');
    }

    public function testHandleWebsiteChange()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $this->handler->handleWebsiteChange($website);
        // Check that same messages are merged
        $this->handler->handleWebsiteChange($website);

        $this->flushMessagesBuffer();

        self::assertMessagesCount(MassRebuildCombinedPriceListsTopic::getName(), 1);
        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website' => $website->getId()
                    ]
                ]
            ]
        );
    }

    public function testHandleNewWebsite()
    {
        $website = new Website();
        $website->setName('TEST WS');
        $this->handler->handleWebsiteChange($website);

        $em = $this->getEntityManager(Website::class);
        $em->persist($website);
        $em->flush();

        self::assertNotEmpty($website->getId());
        self::assertMessagesCount(MassRebuildCombinedPriceListsTopic::getName(), 1);
        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website' => $website->getId()
                    ]
                ]
            ]
        );
    }

    public function testHandleCustomerChange()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var Customer $customer */
        $customer = $this->getReference('customer.level_1');

        $this->handler->handleCustomerChange($customer, $website);
        // Check that same messages are merged
        $this->handler->handleCustomerChange($customer, $website);

        $this->flushMessagesBuffer();

        self::assertMessagesCount(MassRebuildCombinedPriceListsTopic::getName(), 1);
        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'       => $website->getId(),
                        'customer'      => $customer->getId(),
                        'customerGroup' => $customer->getGroup()->getId()
                    ]
                ]
            ]
        );
    }

    public function testHandleNewCustomer()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var User $owner */
        $owner = $this->getReference('user');

        $customer = new Customer();
        $customer->setName('CUSTOMER');
        $customer->setOwner($owner);
        $customer->setOrganization($owner->getOrganization());

        $this->handler->handleCustomerChange($customer, $website);

        $em = $this->getEntityManager(Customer::class);
        $em->persist($customer);
        $em->flush();

        self::assertNotEmpty($customer->getId());
        self::assertMessagesCount(MassRebuildCombinedPriceListsTopic::getName(), 1);
        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'  => $website->getId(),
                        'customer' => $customer->getId()
                    ]
                ]
            ]
        );
    }

    public function testHandleNewCustomerWithGroup()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var CustomerGroup $group */
        $group = $this->getReference('customer_group.group1');
        /** @var User $owner */
        $owner = $this->getReference('user');

        $customer = new Customer();
        $customer->setName('CUSTOMER');
        $customer->setOwner($owner);
        $customer->setGroup($group);
        $customer->setOrganization($owner->getOrganization());

        $this->handler->handleCustomerChange($customer, $website);

        $em = $this->getEntityManager(Customer::class);
        $em->persist($customer);
        $em->flush();

        self::assertNotEmpty($customer->getId());
        self::assertMessagesCount(MassRebuildCombinedPriceListsTopic::getName(), 1);
        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'       => $website->getId(),
                        'customer'      => $customer->getId(),
                        'customerGroup' => $group->getId()
                    ]
                ]
            ]
        );
    }

    public function testHandleConfigChange()
    {
        $this->handler->handleConfigChange();

        $this->flushMessagesBuffer();

        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [[]]
            ]
        );
    }

    public function testHandleCustomerGroupChange()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference(LoadGroups::GROUP1);
        $this->handler->handleCustomerGroupChange($customerGroup, $website);
        // Check that same messages are merged
        $this->handler->handleCustomerGroupChange($customerGroup, $website);

        $this->flushMessagesBuffer();

        self::assertMessagesCount(MassRebuildCombinedPriceListsTopic::getName(), 1);
        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'       => $website->getId(),
                        'customerGroup' => $customerGroup->getId()
                    ]
                ]
            ]
        );
    }

    public function testHandleNewCustomerGroup()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var User $owner */
        $owner = $this->getReference('user');

        $group = new CustomerGroup();
        $group->setName('CUSTOMER GR');
        $group->setOwner($owner);
        $group->setOrganization($owner->getOrganization());

        $this->handler->handleCustomerGroupChange($group, $website);

        $em = $this->getEntityManager(CustomerGroup::class);
        $em->persist($group);
        $em->flush();

        self::assertNotEmpty($group->getId());
        self::assertMessagesCount(MassRebuildCombinedPriceListsTopic::getName(), 1);
        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'       => $website->getId(),
                        'customerGroup' => $group->getId()
                    ]
                ]
            ]
        );
    }

    public function testHandleMoreThanOneCustomerGroup()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        /** @var User $owner */
        $owner = $this->getReference('user');

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference(LoadGroups::GROUP1);

        $group1 = new CustomerGroup();
        $group1->setName('CUSTOMER GR 1');
        $group1->setOwner($owner);
        $group1->setOrganization($owner->getOrganization());

        $group2 = new CustomerGroup();
        $group2->setName('CUSTOMER GR 2');
        $group2->setOwner($owner);
        $group2->setOrganization($owner->getOrganization());

        $this->handler->handleCustomerGroupChange($customerGroup, $website);
        $this->handler->handleCustomerGroupChange($group1, $website);
        $this->handler->handleCustomerGroupChange($group2, $website);

        $em = $this->getEntityManager(CustomerGroup::class);
        $em->persist($group1);
        $em->persist($group2);
        $em->flush();

        self::assertNotEmpty($group1->getId());
        self::assertNotEmpty($group2->getId());
        self::assertMessagesCount(MassRebuildCombinedPriceListsTopic::getName(), 1);
        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'customerGroup' => $customerGroup->getId(),
                        'website'       => $website->getId()
                    ],
                    [
                        'customerGroup' => $group1->getId(),
                        'website'       => $website->getId()
                    ],
                    [
                        'customerGroup' => $group2->getId(),
                        'website'       => $website->getId()
                    ]
                ]
            ]
        );
    }

    public function testHandleFullRebuild()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        // Add website change to be sure that on full rebuild only Full rebuild message will be sent
        $this->handler->handleWebsiteChange($website);

        $this->handler->handleFullRebuild();

        $this->flushMessagesBuffer();

        self::assertMessagesCount(MassRebuildCombinedPriceListsTopic::getName(), 1);
        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'force' => true
                    ]
                ]
            ]
        );
    }

    public function testHandleCustomerGroupRemove()
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference(LoadGroups::GROUP1);
        $this->handler->handleCustomerGroupRemove($customerGroup);

        $this->flushMessagesBuffer();

        self::assertMessagesCount(MassRebuildCombinedPriceListsTopic::getName(), 1);
        self::assertMessageSent(
            MassRebuildCombinedPriceListsTopic::getName(),
            [
                'assignments' => [
                    [
                        'website'       => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                        'customer'      => $this->getReference('customer.level_1.3')->getId()
                    ]
                ]
            ]
        );
    }

    /**
     * @dataProvider duplicateMessagesDataProvider
     *
     * @param string $priceListReference
     * @param array  $fallbackSettings
     * @param array  $expectedMessages
     */
    public function testDuplicateMessagesOnHandlePriceListStatusChange(
        $priceListReference,
        array $fallbackSettings,
        array $expectedMessages
    ) {
        $priceList = $this->getReference($priceListReference);
        $this->resolveReferences($expectedMessages);
        $this->resolveReferences($fallbackSettings);

        $this->createFallbacks($fallbackSettings);

        $this->handler->handlePriceListStatusChange($priceList);

        $this->flushMessagesBuffer();

        $this->resolveIds($expectedMessages);
        self::assertMessagesSent(MassRebuildCombinedPriceListsTopic::getName(), $expectedMessages);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function duplicateMessagesDataProvider(): array
    {
        return [
            'W:t, G:t, C:t' => [
                LoadPriceLists::PRICE_LIST_6,
                [],
                [
                    [
                        'assignments' => [
                            ['website' => LoadWebsiteData::WEBSITE1]
                        ]
                    ]
                ]
            ],
            'W:t, G:f, C:t' => [
                LoadPriceLists::PRICE_LIST_6,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'group'   => 'customer_group.group1'
                    ],
                ],
                [
                    [
                        'assignments' => [
                            [
                                'customerGroup' => 'customer_group.group1',
                                'website'       => LoadWebsiteData::WEBSITE1,
                            ],
                            [
                                'website' => LoadWebsiteData::WEBSITE1
                            ]
                        ]
                    ]
                ]
            ],
            'W:t, G:f, C:f' => [
                LoadPriceLists::PRICE_LIST_6,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'group'   => 'customer_group.group1'
                    ],
                    [
                        'website'  => LoadWebsiteData::WEBSITE1,
                        'customer' => 'customer.level_1.3'
                    ]
                ],
                [
                    [
                        'assignments' => [
                            [
                                'customerGroup' => 'customer_group.group1',
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customer'      => 'customer.level_1.3'
                            ],
                            [
                                'customerGroup' => 'customer_group.group1',
                                'website'       => LoadWebsiteData::WEBSITE1,
                            ],
                            [
                                'website' => LoadWebsiteData::WEBSITE1
                            ]
                        ]
                    ]
                ],
            ],
            'W:t, G:t, C:f' => [
                LoadPriceLists::PRICE_LIST_6,
                [
                    [
                        'website'  => LoadWebsiteData::WEBSITE1,
                        'customer' => 'customer.level_1.3'
                    ]
                ],
                [
                    [
                        'assignments' => [
                            [
                                'customerGroup' => 'customer_group.group1',
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customer'      => 'customer.level_1.3'
                            ],
                            [
                                'website' => LoadWebsiteData::WEBSITE1
                            ]
                        ]
                    ]
                ],
            ],
            'W:n, G:n, C:t' => [
                LoadPriceLists::PRICE_LIST_2,
                [
                ],
                [
                    [
                        'assignments' => [
                            [
                                'customer'      => 'customer.level_1.2',
                                'customerGroup' => 'customer_group.group2',
                                'website'       => LoadWebsiteData::WEBSITE1,
                            ],
                            [
                                'customer'      => 'customer.level_1.3',
                                'customerGroup' => 'customer_group.group1',
                                'website'       => LoadWebsiteData::WEBSITE1,
                            ],
                            [
                                'customer' => 'customer.level_1_1',
                                'website'  => LoadWebsiteData::WEBSITE1,
                            ]
                        ]
                    ]
                ],
            ],
            'W:n, G:n, C:f' => [
                LoadPriceLists::PRICE_LIST_2,
                [
                    [
                        'website'  => LoadWebsiteData::WEBSITE1,
                        'customer' => 'customer.level_1.2'
                    ]
                ],
                [
                    [
                        'assignments' => [
                            [
                                'customer'      => 'customer.level_1.2',
                                'customerGroup' => 'customer_group.group2',
                                'website'       => LoadWebsiteData::WEBSITE1,
                            ],
                            [
                                'customer'      => 'customer.level_1.3',
                                'customerGroup' => 'customer_group.group1',
                                'website'       => LoadWebsiteData::WEBSITE1,
                            ],
                            [
                                'customer' => 'customer.level_1_1',
                                'website'  => LoadWebsiteData::WEBSITE1,
                            ],
                        ]
                    ]
                ],
            ],
            'W:n, G:t, C:t' => [
                LoadPriceLists::PRICE_LIST_4,
                [
                ],
                [
                    [
                        'assignments' => [
                            [
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customerGroup' => 'customer_group.group1'
                            ],
                            [
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customerGroup' => 'customer_group.group2'
                            ]
                        ]
                    ]
                ]
            ],
            'W:n, G:f, C:t' => [
                LoadPriceLists::PRICE_LIST_4,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'group'   => 'customer_group.group1'
                    ]
                ],
                [
                    [
                        'assignments' => [
                            [
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customerGroup' => 'customer_group.group1'
                            ],
                            [
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customerGroup' => 'customer_group.group2'
                            ]
                        ]
                    ]
                ]
            ],
            'W:n, G:t, C:f' => [
                LoadPriceLists::PRICE_LIST_4,
                [
                    [
                        'website'  => LoadWebsiteData::WEBSITE1,
                        'customer' => 'customer.level_1.3'
                    ]
                ],
                [
                    [
                        'assignments' => [
                            [
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customerGroup' => 'customer_group.group1',
                                'customer'      => 'customer.level_1.3'
                            ],
                            [
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customerGroup' => 'customer_group.group1'
                            ],
                            [
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customerGroup' => 'customer_group.group2'
                            ]
                        ]
                    ]
                ]
            ],
            'W:n, G:f, C:f' => [
                LoadPriceLists::PRICE_LIST_4,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'group'   => 'customer_group.group1'
                    ],
                    [
                        'website'  => LoadWebsiteData::WEBSITE1,
                        'customer' => 'customer.level_1.3'
                    ]
                ],
                [
                    [
                        'assignments' => [
                            [
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customerGroup' => 'customer_group.group1',
                                'customer'      => 'customer.level_1.3'
                            ],
                            [
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customerGroup' => 'customer_group.group1'
                            ],
                            [
                                'website'       => LoadWebsiteData::WEBSITE1,
                                'customerGroup' => 'customer_group.group2'
                            ],
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass($entityClass);
    }

    private function resolveReferences(array &$data): void
    {
        foreach ($data as &$item) {
            if (is_array($item)) {
                $this->resolveReferences($item);
            } else {
                $item = $this->getReference($item);
            }
        }
    }

    private function resolveIds(array &$expectedMessages): void
    {
        foreach ($expectedMessages as &$item) {
            if (is_array($item)) {
                $this->resolveIds($item);
            } else {
                $item = $item->getId();
            }
        }
    }

    private function createFallbacks(array $fallbackSettings): void
    {
        foreach ($fallbackSettings as $fallbackData) {
            if (array_key_exists('customer', $fallbackData)) {
                $fallback = new PriceListCustomerFallback();
                $fallback->setCustomer($fallbackData['customer']);
            } else {
                $fallback = new PriceListCustomerGroupFallback();
                $fallback->setCustomerGroup($fallbackData['group']);
            }
            $fallback->setWebsite($fallbackData['website']);
            $fallback->setFallback(1);

            $em = $this->getEntityManager(get_class($fallback));
            $em->persist($fallback);
            $em->flush($fallback);
        }
    }
}
