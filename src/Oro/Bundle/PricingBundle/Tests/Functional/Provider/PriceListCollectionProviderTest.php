<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class PriceListCollectionProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const DEFAULT_PRICE_LIST = 1;

    private PriceListCollectionProvider $provider;

    protected function setUp(): void
    {
        $this->initClient([]);
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadPriceListFallbackSettings::class,
            LoadPriceListRelations::class,
        ]);

        $this->provider = $this->getContainer()->get('oro_pricing.provider.price_list_collection');
    }

    public function testGetPriceListsByConfig()
    {
        $this->setPriceListToConfig();
        $pricesChain = $this->provider->getPriceListsByConfig();
        $this->assertCount(1, $pricesChain);
        $this->assertTrue($pricesChain[0]->isMergeAllowed());
    }

    /**
     * @dataProvider testGetPriceListsByWebsiteDataProvider
     */
    public function testGetPriceListsByWebsite(string $websiteReference, array $expectedPriceLists)
    {
        $expectedPriceLists = $this->resolveExpectedPriceLists($expectedPriceLists);

        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $result = $this->provider->getPriceListsByWebsite($website);
        $this->assertEquals($expectedPriceLists, $this->resolveResult($result));
    }

    public function testGetPriceListsByWebsiteDataProvider(): array
    {
        return [
            'website with settings and enabled fallback' => [
                'websiteReference' => 'US',
                'expectedPriceListNames' => [
                    /** From Website */
                    [
                        'priceList' => 'price_list_3',
                        'mergeAllowed' => false,
                    ],
                    [
                        'priceList' => 'price_list_1',
                        'mergeAllowed' => true,
                    ],
                    /** End From Website */
                    /** From config */
                    [
                        'priceList' => self::DEFAULT_PRICE_LIST,
                        'mergeAllowed' => true,
                    ],
                    /** End From config */
                ],
            ],
            'website with settings and blocked fallback' => [
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => [
                    /** From Website */
                    [
                        'priceList' => 'price_list_3',
                        'mergeAllowed' => true,
                    ],
                    /** End From Website */
                ],
            ],
            'no website specific settings' => [
                'websiteReference' => 'CA',
                'expectedPriceListNames' => [
                    /** From Website */
                    /** End From Website */
                    /** From config */
                    [
                        'priceList' => self::DEFAULT_PRICE_LIST,
                        'mergeAllowed' => true,
                    ],
                    /** End From config */
                ],
            ],
        ];
    }

    /**
     * @dataProvider testGetPriceListsByCustomerGroupDataProvider
     */
    public function testGetPriceListsByCustomerGroup(
        string $customerGroupReference,
        string $websiteReference,
        array $expectedPriceLists
    ) {
        $expectedPriceLists = $this->resolveExpectedPriceLists($expectedPriceLists);

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference($customerGroupReference);
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $result = $this->provider->getPriceListsByCustomerGroup($customerGroup, $website);
        $this->assertEquals($expectedPriceLists, $this->resolveResult($result));
    }

    public function testGetPriceListsByCustomerGroupDataProvider(): array
    {
        return [
            'all fallbacks' => [
                'customerGroupReference' => 'customer_group.group1',
                'websiteReference' => 'US',
                'expectedPriceListNames' => [
                    /** From group */
                    [
                        'priceList' => 'price_list_5',
                        'mergeAllowed' => false,
                    ],
                    [
                        'priceList' => 'price_list_1',
                        'mergeAllowed' => true,
                    ],
                    [
                        'priceList' => 'price_list_6', // Not active.
                        'mergeAllowed' => false
                    ],
                    /** End From group */
                    /** From Website */
                    [
                        'priceList' => 'price_list_3',
                        'mergeAllowed' => false,
                    ],
                    [
                        'priceList' => 'price_list_1',
                        'mergeAllowed' => true,
                    ],
                    /** End From Website */
                    /** From config */
                    [
                        'priceList' => self::DEFAULT_PRICE_LIST,
                        'mergeAllowed' => true,
                    ],
                    /** End From config */
                ],
            ],
            'no group settings only website with blocked fallback' => [
                'customerGroupReference' => 'customer_group.group1',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => [
                    /** From group */
                    /** End From group */
                    /** From Website */
                    [
                        'priceList' => 'price_list_3',
                        'mergeAllowed' => true,
                    ],
                    /** End From Website */
                ],
            ],
            'group with blocked fallback' => [
                'customerGroupReference' => 'customer_group.group2',
                'websiteReference' => 'US',
                'expectedPriceListNames' => [
                    /** From group */
                    [
                        'priceList' => 'price_list_4',
                        'mergeAllowed' => true,
                    ],
                    /** End From group */
                ],
            ],
            'group with blocked fallback no group price lists' => [
                'customerGroupReference' => 'customer_group.group2',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => [],
            ],
            'group without settings' => [
                'customerGroupReference' => 'customer_group.group3',
                'websiteReference' => 'US',
                'expectedPriceListNames' => [
                    /** From Website */
                    [
                        'priceList' => 'price_list_3',
                        'mergeAllowed' => false,
                    ],
                    [
                        'priceList' => 'price_list_1',
                        'mergeAllowed' => true,
                    ],
                    /** End From Website */
                    /** From config */
                    [
                        'priceList' => self::DEFAULT_PRICE_LIST,
                        'mergeAllowed' => true,
                    ],
                    /** End From config */
                ],
            ],
        ];
    }

    /**
     * @dataProvider testGetPriceListsByCustomerDataProvider
     */
    public function testGetPriceListsByCustomer(
        string $customerReference,
        string $websiteReference,
        array $expectedPriceLists
    ) {
        $expectedPriceLists = $this->resolveExpectedPriceLists($expectedPriceLists);

        /** @var Customer $customer */
        $customer = $this->getReference($customerReference);
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $result = $this->provider->getPriceListsByCustomer($customer, $website);
        $this->assertEquals($expectedPriceLists, $this->resolveResult($result));
    }

    public function testGetPriceListsByCustomerDataProvider(): array
    {
        return [
            'customer.orphan Canada' => [
                'customerReference' => 'customer.orphan',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => [
                    /** From Website */
                    [
                        'priceList' => 'price_list_3',
                        'mergeAllowed' => true,
                    ],
                    /** End From Website */
                ],
            ],
            'customer.level_1.2 US' => [
                'customerReference' => 'customer.level_1.2',
                'websiteReference' => 'US',
                'expectedPriceListNames' => [
                    [
                        'priceList' => 'price_list_2',
                        'mergeAllowed' => true,
                    ],
                ],
            ],
            'customer.level_1.2 Canada' => [
                'customerReference' => 'customer.level_1.2',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => [
                ],
            ],
        ];
    }

    /**
     * @dataProvider getPriceListsByCustomerForCustomerWithoutGroupDataProvider
     */
    public function testGetPriceListsByCustomerForCustomerWithoutGroup(string $website, array $expectedPriceLists)
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.level_1_1');
        $this->assertNull($customer->getGroup());

        /** @var Website $website */
        $website = $this->getReference($website);

        $expectedPriceLists = $this->resolveExpectedPriceLists($expectedPriceLists);
        $result = $this->provider->getPriceListsByCustomer($customer, $website);
        $this->assertEquals($expectedPriceLists, $this->resolveResult($result));
    }

    public function getPriceListsByCustomerForCustomerWithoutGroupDataProvider(): array
    {
        return [
            'current customer only' => [
                'websiteReference' => 'Canada',
                'expectedPriceLists' => [
                    /** From customer */
                    [
                        'priceList' => 'price_list_1',
                        'mergeAllowed' => true,
                    ],
                    /** End From customer */
                ]
            ],
            'customer group fallback' => [
                'websiteReference' => 'US',
                'expectedPriceLists' => [
                    /** From customer */
                    [
                        'priceList' => 'price_list_2',
                        'mergeAllowed' => false,
                    ],
                    [
                        'priceList' => 'price_list_1',
                        'mergeAllowed' => true,
                    ],
                    /** End From customer */
                    /** From group */
                    /** End From Group */
                    /** From Website */
                    [
                        'priceList' => 'price_list_3',
                        'mergeAllowed' => false,
                    ],
                    [
                        'priceList' => 'price_list_1',
                        'mergeAllowed' => true,
                    ],
                    /** End From Website */
                    /** From config */
                    [
                        'priceList' => self::DEFAULT_PRICE_LIST,
                        'mergeAllowed' => true,
                    ],
                    /** End From config */
                ]
            ]
        ];
    }

    private function resolveExpectedPriceLists(array $expectedPriceLists): array
    {
        $result = [];
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        foreach ($expectedPriceLists as $expectedPriceListData) {
            if ($expectedPriceListData['priceList'] === self::DEFAULT_PRICE_LIST) {
                $priceList = $em->getReference(PriceList::class, self::DEFAULT_PRICE_LIST);
            } else {
                $priceList = $this->getReference($expectedPriceListData['priceList']);
            }
            $result[] = [
                'priceList' => $priceList->getName(),
                'mergeAllowed' => $expectedPriceListData['mergeAllowed']
            ];
        }

        return $result;
    }

    private function setPriceListToConfig(): void
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $priceList = $em->getReference(PriceList::class, self::DEFAULT_PRICE_LIST);

        $configManager = self::getConfigManager();
        $configManager->set(
            'oro_pricing.default_price_lists',
            [new PriceListConfig($priceList, 100, true)]
        );
        $configManager->flush();
    }

    private function resolveResult(array $sequenceMembers): array
    {
        $result = [];
        /** @var PriceListSequenceMember $sequenceMember */
        foreach ($sequenceMembers as $sequenceMember) {
            $result[] = [
                'priceList' => $sequenceMember->getPriceList()->getName(),
                'mergeAllowed' => $sequenceMember->isMergeAllowed()
            ];
        }

        return $result;
    }
}
