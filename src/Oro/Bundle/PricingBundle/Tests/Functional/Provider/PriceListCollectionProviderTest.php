<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\PricingBundle\SystemConfig\PriceListConfig;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class PriceListCollectionProviderTest extends WebTestCase
{
    const DEFAULT_PRICE_LIST = 1;

    /**
     * @var PriceListCollectionProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->initClient([]);
        $this->provider = $this->getContainer()->get('orob2b_pricing.provider.price_list_collection');

        $this->loadFixtures(
            [
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
            ]
        );
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
     * @param string $websiteReference
     * @param array $expectedPriceLists
     */
    public function testGetPriceListsByWebsite($websiteReference, array $expectedPriceLists)
    {
        $expectedPriceLists = $this->resolveExpectedPriceLists($expectedPriceLists);

        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $result = $this->provider->getPriceListsByWebsite($website);
        $this->assertEquals($expectedPriceLists, $this->resolveResult($result));
    }

    /**
     * @return array
     */
    public function testGetPriceListsByWebsiteDataProvider()
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
     * @dataProvider testGetPriceListsByAccountGroupDataProvider
     *
     * @param string $accountGroupReference
     * @param string $websiteReference
     * @param array $expectedPriceLists
     */
    public function testGetPriceListsByAccountGroup(
        $accountGroupReference,
        $websiteReference,
        array $expectedPriceLists
    ) {
        $expectedPriceLists = $this->resolveExpectedPriceLists($expectedPriceLists);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroupReference);
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $result = $this->provider->getPriceListsByAccountGroup($accountGroup, $website);
        $this->assertEquals($expectedPriceLists, $this->resolveResult($result));
    }

    /**
     * @return array
     */
    public function testGetPriceListsByAccountGroupDataProvider()
    {
        return [
            'all fallbacks' => [
                'accountGroupReference' => 'account_group.group1',
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
                'accountGroupReference' => 'account_group.group1',
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
                'accountGroupReference' => 'account_group.group2',
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
                'accountGroupReference' => 'account_group.group2',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => [],
            ],
            'group without settings' => [
                'accountGroupReference' => 'account_group.group3',
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
     * @dataProvider testGetPriceListsByAccountDataProvider
     *
     * @param string $accountReference
     * @param string $websiteReference
     * @param array $expectedPriceLists
     */
    public function testGetPriceListsByAccount(
        $accountReference,
        $websiteReference,
        array $expectedPriceLists
    ) {
        $expectedPriceLists = $this->resolveExpectedPriceLists($expectedPriceLists);

        /** @var Account $account */
        $account = $this->getReference($accountReference);
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $result = $this->provider->getPriceListsByAccount($account, $website);
        $this->assertEquals($expectedPriceLists, $this->resolveResult($result));
    }

    /**
     * @return array
     */
    public function testGetPriceListsByAccountDataProvider()
    {
        return [
            'account.level_1_1 US' => [
                'accountReference' => 'account.level_1_1',
                'websiteReference' => 'US',
                'expectedPriceListNames' => [
                    /** From account */
                    [
                        'priceList' => 'price_list_2',
                        'mergeAllowed' => false,
                    ],
                    [
                        'priceList' => 'price_list_1',
                        'mergeAllowed' => true,
                    ],
                    /** End From account */
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
                ],
            ],
            'account.orphan Canada' => [
                'accountReference' => 'account.orphan',
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
            'account.level_1.2 US' => [
                'accountReference' => 'account.level_1.2',
                'websiteReference' => 'US',
                'expectedPriceListNames' => [
                    [
                        'priceList' => 'price_list_2',
                        'mergeAllowed' => true,
                    ],
                ],
            ],
            'account.level_1.2 Canada' => [
                'accountReference' => 'account.level_1.2',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => [
                ],
            ],
        ];
    }

    /**
     * @param array $expectedPriceLists
     * @return array
     */
    protected function resolveExpectedPriceLists(array $expectedPriceLists)
    {
        $result = [];
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        foreach ($expectedPriceLists as $expectedPriceListData) {
            if ($expectedPriceListData['priceList'] === self::DEFAULT_PRICE_LIST) {
                $priceList = $em->getReference('OroPricingBundle:PriceList', self::DEFAULT_PRICE_LIST);
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

    protected function setPriceListToConfig()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $priceList = $em->getReference('OroPricingBundle:PriceList', self::DEFAULT_PRICE_LIST);

        $configManager = $this->getContainer()->get('oro_config.global');
        $configManager->set(
            'oro_b2b_pricing.default_price_lists',
            [new PriceListConfig($priceList, 100, true)]
        );
        $configManager->flush();
    }

    /**
     * @param PriceListSequenceMember[] $sequenceMembers
     * @return array
     */
    protected function resolveResult(array $sequenceMembers)
    {
        $result = [];
        foreach ($sequenceMembers as $sequenceMember) {
            $result[] = [
                'priceList' => $sequenceMember->getPriceList()->getName(),
                'mergeAllowed' => $sequenceMember->isMergeAllowed()
            ];
        }

        return $result;
    }
}
