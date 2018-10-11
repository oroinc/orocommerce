<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Model\DTO\PriceListRelationTrigger;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelationsForTriggers;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolationPerTest
 */
class PriceListRelationTriggerHandlerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * @var PriceListRelationTriggerHandler
     */
    protected $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadPriceListRelationsForTriggers::class,
            ]
        );

        $this->handler = $this->getContainer()->get('oro_pricing.price_list_relation_trigger_handler');
    }

    public function testHandleWebsiteChange()
    {
        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE1);
        $this->handler->handleWebsiteChange($website);
        // Check that same messages are merged
        $this->handler->handleWebsiteChange($website);
        $this->handler->sendScheduledTriggers();

        self::assertMessagesCount(Topics::REBUILD_COMBINED_PRICE_LISTS, 1);
        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $website->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
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
        $this->handler->sendScheduledTriggers();

        self::assertMessagesCount(Topics::REBUILD_COMBINED_PRICE_LISTS, 1);
        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $website->getId(),
                PriceListRelationTrigger::ACCOUNT => $customer->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => $customer->getGroup()->getId(),
                PriceListRelationTrigger::FORCE => false,
            ]
        );
    }

    public function testHandleConfigChange()
    {
        $this->handler->handleConfigChange();
        $this->handler->sendScheduledTriggers();

        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => null,
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => false,
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
        $this->handler->sendScheduledTriggers();

        self::assertMessagesCount(Topics::REBUILD_COMBINED_PRICE_LISTS, 1);
        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $website->getId(),
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => $customerGroup->getId(),
                PriceListRelationTrigger::FORCE => false,
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
        $this->handler->sendScheduledTriggers();

        self::assertMessagesCount(Topics::REBUILD_COMBINED_PRICE_LISTS, 1);
        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => null,
                PriceListRelationTrigger::ACCOUNT => null,
                PriceListRelationTrigger::ACCOUNT_GROUP => null,
                PriceListRelationTrigger::FORCE => true,
            ]
        );
    }

    public function testHandleCustomerGroupRemove()
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference(LoadGroups::GROUP1);
        $this->handler->handleCustomerGroupRemove($customerGroup);
        $this->handler->sendScheduledTriggers();

        self::assertMessagesCount(Topics::REBUILD_COMBINED_PRICE_LISTS, 1);
        self::assertMessageSent(
            Topics::REBUILD_COMBINED_PRICE_LISTS,
            [
                PriceListRelationTrigger::WEBSITE => $this->getReference(LoadWebsiteData::WEBSITE1)->getId(),
                PriceListRelationTrigger::ACCOUNT => $this->getReference('customer.level_1.3')->getId(),
                PriceListRelationTrigger::ACCOUNT_GROUP => null
            ]
        );
    }

    /**
     * @dataProvider duplicateMessagesDataProvider
     *
     * @param string $priceListReference
     * @param array $fallbackSettings
     * @param array $expectedMessages
     */
    public function testDuplicateMessagesOnHandlePriceListStatusChange(
        $priceListReference,
        array $fallbackSettings,
        array $expectedMessages
    ) {
        $priceList = $this->getReference($priceListReference);
        $this->createFallbacks($fallbackSettings);

        $this->handler->handlePriceListStatusChange($priceList);
        $this->handler->sendScheduledTriggers();

        $this->resolveIds($expectedMessages);
        self::assertMessagesSent(Topics::REBUILD_COMBINED_PRICE_LISTS, $expectedMessages);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function duplicateMessagesDataProvider(): array
    {
        return [
            'W:t, G:t, C:t' => [
                LoadPriceLists::PRICE_LIST_6,
                [],
                [
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::ACCOUNT => null
                    ]

                ]
            ],
            'W:t, G:f, C:t' => [
                LoadPriceLists::PRICE_LIST_6,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'group' => 'customer_group.group1'
                    ]
                ],
                [
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::ACCOUNT => null
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => null
                    ]
                ]
            ],
            'W:t, G:f, C:f' => [
                LoadPriceLists::PRICE_LIST_6,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'group' => 'customer_group.group1'
                    ],
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'customer' => 'customer.level_1.3'
                    ]
                ],
                [
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::ACCOUNT => null
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => null
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => 'customer.level_1.3'
                    ]
                ],
            ],
            'W:t, G:t, C:f' => [
                LoadPriceLists::PRICE_LIST_6,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'customer' => 'customer.level_1.3'
                    ]
                ],
                [
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::ACCOUNT => null
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => 'customer.level_1.3'
                    ]
                ],
            ],
            'W:n, G:n, C:t' => [
                LoadPriceLists::PRICE_LIST_2,
                [
                ],
                [
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::ACCOUNT => 'customer.level_1_1'
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group2',
                        PriceListRelationTrigger::ACCOUNT => 'customer.level_1.2'
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => 'customer.level_1.3'
                    ]
                ],
            ],
            'W:n, G:n, C:f' => [
                LoadPriceLists::PRICE_LIST_2,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'customer' => 'customer.level_1.2'
                    ]
                ],
                [
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => null,
                        PriceListRelationTrigger::ACCOUNT => 'customer.level_1_1'
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group2',
                        PriceListRelationTrigger::ACCOUNT => 'customer.level_1.2'
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => 'customer.level_1.3'
                    ]
                ],
            ],
            'W:n, G:t, C:t' => [
                LoadPriceLists::PRICE_LIST_4,
                [
                ],
                [
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => null
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group2',
                        PriceListRelationTrigger::ACCOUNT => null
                    ]
                ]
            ],
            'W:n, G:f, C:t' => [
                LoadPriceLists::PRICE_LIST_4,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'group' => 'customer_group.group1'
                    ]
                ],
                [
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => null
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group2',
                        PriceListRelationTrigger::ACCOUNT => null
                    ]
                ]
            ],
            'W:n, G:t, C:f' => [
                LoadPriceLists::PRICE_LIST_4,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'customer' => 'customer.level_1.3'
                    ]
                ],
                [
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => null
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => 'customer.level_1.3'
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group2',
                        PriceListRelationTrigger::ACCOUNT => null
                    ]
                ]
            ],
            'W:n, G:f, C:f' => [
                LoadPriceLists::PRICE_LIST_4,
                [
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'group' => 'customer_group.group1'
                    ],
                    [
                        'website' => LoadWebsiteData::WEBSITE1,
                        'customer' => 'customer.level_1.3'
                    ]
                ],
                [
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => null
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group1',
                        PriceListRelationTrigger::ACCOUNT => 'customer.level_1.3'
                    ],
                    [
                        PriceListRelationTrigger::WEBSITE => LoadWebsiteData::WEBSITE1,
                        PriceListRelationTrigger::ACCOUNT_GROUP => 'customer_group.group2',
                        PriceListRelationTrigger::ACCOUNT => null
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $data
     */
    protected function resolveReferences(array &$data)
    {
        foreach ($data as &$item) {
            foreach ($item as $key => $reference) {
                if ($reference) {
                    $item[$key] = $this->getReference($reference);
                }
            }
        }
    }

    /**
     * @param array $expectedMessages
     */
    protected function resolveIds(array &$expectedMessages)
    {
        $this->resolveReferences($expectedMessages);
        foreach ($expectedMessages as &$expectedMessage) {
            foreach ($expectedMessage as $key => $value) {
                if ($value) {
                    $expectedMessage[$key] = $value->getId();
                }
            }
        }
    }

    /**
     * @param array $fallbackSettings
     */
    protected function createFallbacks(array $fallbackSettings)
    {
        $this->resolveReferences($fallbackSettings);
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

            $em = $this->getContainer()->get('doctrine')->getManagerForClass(get_class($fallback));
            $em->persist($fallback);
            $em->flush($fallback);
        }
    }
}
