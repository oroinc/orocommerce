<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Provider;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class PriceListCollectionProviderTest extends WebTestCase
{
    /** @var  PriceListCollectionProvider */
    protected $provider;

    protected function setUp()
    {
        $this->initClient([]);
        $this->provider = $this->getContainer()->get('orob2b_pricing.provider.price_list_collection');

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadLevelFallbacks',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists',
            ]
        );
    }

    public function testGetPriceListsByConfig()
    {
        $pricesChain = $this->provider->getPriceListsByConfig();
        $this->assertCount(1, $pricesChain);
        $this->assertTrue($pricesChain[0]->isMergeAllowed());
        $this->assertTrue($pricesChain[0]->getPriceList()->isDefault());
    }

    /**
     * @dataProvider testGetPriceListsByWebsiteDataProvider
     * @param string $websiteReference
     * @param array $expectedPriceListNames
     */
    public function testGetPriceListsByWebsite($websiteReference, array $expectedPriceListNames)
    {
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $result = $this->provider->getPriceListsByWebsite($website);
        $this->assertCount(count($expectedPriceListNames), $result);
        foreach ($expectedPriceListNames as $index => $priceListName) {
            $this->assertEquals($priceListName, $result[$index]->getPriceList()->getName());
        }
    }

    public function testGetPriceListsByWebsiteDataProvider()
    {
        return [
            [
                'websiteReference' => 'US',
                'expectedPriceListNames' => [
                    'priceList1',
                    'Default Price List',
                ],
            ],
            [
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => [
                    'priceList3',
                ],
            ],
        ];
    }

    /**
     * @dataProvider testGetPriceListsByAccountGroupDataProvider
     *
     * @param string $accountGroupReference
     * @param string $websiteReference
     * @param array $expectedPriceListNames
     */
    public function testGetPriceListsByAccountGroup(
        $accountGroupReference,
        $websiteReference,
        array $expectedPriceListNames
    ) {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroupReference);
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $result = $this->provider->getPriceListsByAccountGroup($accountGroup, $website);
        $this->assertCount(count($expectedPriceListNames), $result);
        foreach ($expectedPriceListNames as $index => $priceListName) {
            $this->assertEquals($priceListName, $result[$index]->getPriceList()->getName());
        }
    }

    public function testGetPriceListsByAccountGroupDataProvider()
    {
        return [
            [
                'accountGroupReference' => 'account_group.group1',
                'websiteReference' => 'US',
                'expectedPriceListNames' => [
                    'priceList1',
                    'priceList1',
                    'Default Price List',
                ],
            ],
            [
                'accountGroupReference' => 'account_group.group1',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => ['priceList3'],
            ],
            [
                'accountGroupReference' => 'account_group.group2',
                'websiteReference' => 'US',
                'expectedPriceListNames' => ['priceList4'],
            ],
            [
                'accountGroupReference' => 'account_group.group2',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => [],
            ],
            [
                'accountGroupReference' => 'account_group.group3',
                'websiteReference' => 'US',
                'expectedPriceListNames' => [],
            ],
            [
                'accountGroupReference' => 'account_group.group3',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => ['priceList5'],
            ],
        ];
    }


    /**
     * @dataProvider testGetPriceListsByAccountDataProvider
     *
     * @param string $accountReference
     * @param string $websiteReference
     * @param array $expectedPriceListNames
     */
    public function testGetPriceListsByAccount(
        $accountReference,
        $websiteReference,
        array $expectedPriceListNames
    ) {
        /** @var Account $account */
        $account = $this->getReference($accountReference);
        /** @var Website $website */
        $website = $this->getReference($websiteReference);
        $result = $this->provider->getPriceListsByAccount($account, $website);
        $this->assertCount(count($expectedPriceListNames), $result);
        foreach ($expectedPriceListNames as $index => $priceListName) {
            $this->assertEquals($priceListName, $result[$index]->getPriceList()->getName());
        }
    }

    public function testGetPriceListsByAccountDataProvider()
    {
        return [
            [
                'accountReference' => 'account.orphan',
                'websiteReference' => 'US',
                'expectedPriceListNames' => [
                    'priceList3',
                ],
            ],
            [
                'accountReference' => 'account.orphan',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => [],
            ],
            [
                'accountReference' => 'account.level_1.1.1',
                'websiteReference' => 'US',
                'expectedPriceListNames' => [],
            ],
            [
                'accountReference' => 'account.level_1.1.1',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => ['priceList5'],
            ],
            [
                'accountReference' => 'account.level_1.2',
                'websiteReference' => 'US',
                'expectedPriceListNames' => ['priceList2', 'priceList4'],
            ],
            [
                'accountReference' => 'account.level_1.2',
                'websiteReference' => 'Canada',
                'expectedPriceListNames' => ['priceList2'],
            ],
        ];
    }
}
