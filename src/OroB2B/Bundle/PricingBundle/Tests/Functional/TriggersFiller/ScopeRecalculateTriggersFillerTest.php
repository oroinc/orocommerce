<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\TriggersFiller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\TriggersFiller\ScopeRecalculateTriggersFiller;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ScopeRecalculateTriggersFillerTest extends WebTestCase
{
    /**
     * @var ScopeRecalculateTriggersFiller
     */
    protected $triggersFiller;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );
        $this->triggersFiller = $this->getContainer()
            ->get('orob2b_pricing.triggers_filler.scope_recalculate_triggers_filler');

        $this->clearTriggers();
    }

    /**
     * @dataProvider fillTriggersForRecalculateDataProvider
     * @param array $websites
     * @param array $accountGroups
     * @param array $accounts
     * @param $expectedPriceListChangeTriggersCount
     */
    public function testFillTriggersForRecalculate(
        array $websites,
        array $accountGroups,
        array $accounts,
        $expectedPriceListChangeTriggersCount
    ) {
        $websiteIds = [];
        $accountGroupIds = [];
        $accountIds = [];

        foreach ($websites as $website) {
            $websiteIds[] = $this->getReference($website)->getId();
        }

        foreach ($accountGroups as $accountGroup) {
            $accountGroupIds[] = $this->getReference($accountGroup)->getId();
        }

        foreach ($accounts as $account) {
            $accountIds[] = $this->getReference($account)->getId();
        }

        $this->triggersFiller->fillTriggersForRecalculate($websiteIds, $accountGroupIds, $accountIds);
        $this->assertPriceListChangeTriggersCount($expectedPriceListChangeTriggersCount);
    }

    /**
     * @return array
     */
    public function fillTriggersForRecalculateDataProvider()
    {
        return [
            [
                'websites' => ['US'],
                'accountGroups' => ['account_group.group1'],
                'accounts' => ['account.level_1_1'],
                'priceListChangeTriggersCount' => 2,
            ],
            [
                'websites' => ['US'],
                'accountGroups' => [],
                'accounts' => ['account.level_1_1', 'account.level_1.3'],
                'priceListChangeTriggersCount' => 2,
            ],
            [
                'websites' => ['Canada', 'US'],
                'accountGroups' => [],
                'accounts' => [],
                'priceListChangeTriggersCount' => 2,
            ],
            [
                'websites' => ['US'],
                'accountGroups' => [],
                'accounts' => [],
                'priceListChangeTriggersCount' => 1,
            ],
            [
                'websites' => [],
                'accountGroups' => [],
                'accounts' => [],
                'priceListChangeTriggersCount' => 1,
            ],
        ];
    }

    /**
     * @dataProvider fillTriggersByPriceListDataProvider
     * @param string $priceList
     * @param int $expectedCount
     */
    public function testFillTriggersByPriceList($priceList, $expectedCount)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceList);
        $this->triggersFiller->fillTriggersByPriceList($priceList);

        $this->assertPriceListChangeTriggersCount($expectedCount);
    }

    /**
     * @return array
     */
    public function fillTriggersByPriceListDataProvider()
    {
        return [
            [
                'priceList' => 'price_list_1',
                'expectedCount' => 3
            ],
            [
                'priceList' => 'price_list_2',
                'expectedCount' => 3
            ],
            [
                'priceList' => 'price_list_3',
                'expectedCount' => 2
            ],
            [
                'priceList' => 'price_list_4',
                'expectedCount' => 2
            ],
            [
                'priceList' => 'price_list_5',
                'expectedCount' => 3
            ],

        ];
    }

    public function testCreateTriggerByPriceListProduct()
    {
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPricingBundle:ProductPriceChangeTrigger')
            ->getRepository('OroPricingBundle:ProductPriceChangeTrigger');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $triggers = $repository->findBy(['priceList' => $priceList, 'product' => $product]);
        $this->assertEmpty($triggers);

        $this->triggersFiller->createTriggerByPriceListProduct($priceList, $product);

        $triggers = $repository->findBy(['priceList' => $priceList, 'product' => $product]);
        $this->assertCount(1, $triggers);
    }

    /**
     * @param int $count
     */
    protected function assertPriceListChangeTriggersCount($count)
    {
        $priceListChangeTriggers = $this->getContainer()->get('doctrine')
            ->getRepository('OroPricingBundle:PriceListChangeTrigger')
            ->findAll();
        $this->assertCount($count, $priceListChangeTriggers);
    }

    protected function clearTriggers()
    {
        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPricingBundle:PriceListChangeTrigger')
            ->getRepository('OroPricingBundle:PriceListChangeTrigger')
            ->createQueryBuilder('priceListChangeTrigger')
            ->delete('OroPricingBundle:PriceListChangeTrigger', 'priceListChangeTrigger')
            ->getQuery()
            ->execute();
    }
}
