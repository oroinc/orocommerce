<?php

namespace OroB2B\Bundle\PricingBundle\RecalculateTriggersFiller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
        $this->markTestSkipped('Need correction after BB-2795');
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );
        $this->triggersFiller = $this->getContainer()
            ->get('orob2b_pricing.recalculate_triggers_filler.scope_recalculate_triggers_filler');

        $this->clearTriggers();
    }

    /**
     * @dataProvider fillTriggersForRecalculateDataProvider
     * @param array $websites
     * @param array $accountGroups
     * @param array $accounts
     * @param bool $force
     * @param $expectedPriceListChangeTriggersCount
     */
    public function testFillTriggersForRecalculate(
        array $websites,
        array $accountGroups,
        array $accounts,
        $force,
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

        $this->triggersFiller->fillTriggersForRecalculate($websiteIds, $accountGroupIds, $accountIds, $force);
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
                'force' => false,
                'priceListChangeTriggersCount' => 2,
            ],
            [
                'websites' => ['US'],
                'accountGroups' => [],
                'accounts' => ['account.level_1_1', 'account.level_1.3'],
                'force' => false,
                'priceListChangeTriggersCount' => 2,
            ],
            [
                'websites' => ['US'],
                'accountGroups' => [],
                'accounts' => [],
                'force' => false,
                'priceListChangeTriggersCount' => 1,
            ],
            [
                'websites' => ['Canada', 'US'],
                'accountGroups' => [],
                'accounts' => [],
                'force' => false,
                'priceListChangeTriggersCount' => 2,
            ],
            [
                'websites' => ['US'],
                'accountGroups' => [],
                'accounts' => [],
                'force' => true,
                'priceListChangeTriggersCount' => 1,
            ],
            [
                'websites' => [],
                'accountGroups' => [],
                'accounts' => [],
                'force' => true,
                'priceListChangeTriggersCount' => 0,
            ],
        ];
    }

    /**
     * @param int $count
     */
    protected function assertPriceListChangeTriggersCount($count)
    {
        $priceListChangeTriggers = $this->getContainer()->get('doctrine')
            ->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')
            ->findAll();
        $this->assertCount($count, $priceListChangeTriggers);
    }

    protected function clearTriggers()
    {
        $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:PriceListChangeTrigger')
            ->getRepository('OroB2BPricingBundle:PriceListChangeTrigger')
            ->createQueryBuilder('priceListChangeTrigger')
            ->delete('OroB2BPricingBundle:PriceListChangeTrigger', 'priceListChangeTrigger')
            ->getQuery()
            ->execute();
    }
}
