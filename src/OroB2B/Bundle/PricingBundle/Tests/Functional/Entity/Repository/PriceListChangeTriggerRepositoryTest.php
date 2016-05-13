<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccount;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToWebsite;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListChangeTriggerRepository;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class PriceListChangeTriggerRepositoryTest extends WebTestCase
{
    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListChangeTrigger',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations'
        ]);
        $this->insertFromSelectQueryExecutor = $this->getContainer()
            ->get('oro_entity.orm.insert_from_select_query_executor');
    }

    public function testFindBuildAllForceTrigger()
    {
        $trigger = $this->getRepository()->findBuildAllForceTrigger();
        $this->assertEmpty($trigger->getWebsite());
        $this->assertEmpty($trigger->getAccountGroup());
        $this->assertEmpty($trigger->getAccount());
        $this->assertTrue($trigger->isForce());
    }

    public function testGetPriceListChangeTriggersIterator()
    {
        $iterator = $this->getRepository()->getPriceListChangeTriggersIterator();
        $allChanges = $this->getRepository()->findAll();
        $this->assertInstanceOf('Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator', $iterator);
        $this->assertCount(count($allChanges), $iterator);
    }

    /**
     * @dataProvider clearExistingScopesPriceListChangeTriggersDataProvider
     * @param array $websites
     * @param array $accountGroups
     * @param array $accounts
     * @param array $removedTriggers
     */
    public function testClearExistingScopesPriceListChangeTriggers(
        array $websites,
        array $accountGroups,
        array $accounts,
        array $removedTriggers
    ) {
        $triggers = $this->getRepository()->findAll();
        $removedTriggers = array_map(
            function ($removedTrigger) {
                return $this->getReference($removedTrigger);
            },
            $removedTriggers
        );

        foreach ($removedTriggers as $removedTrigger) {
            $this->assertContains($removedTrigger, $triggers);
        }

        $websites = array_map(
            function ($website) {
                return $this->getReference($website);
            },
            $websites
        );
        $accountGroups = array_map(
            function ($accountGroup) {
                return $this->getReference($accountGroup);
            },
            $accountGroups
        );
        $accounts = array_map(
            function ($account) {
                return $this->getReference($account);
            },
            $accounts
        );

        $this->getRepository()->clearExistingScopesPriceListChangeTriggers($websites, $accountGroups, $accounts);

        $triggers = $this->getRepository()->findAll();
        foreach ($removedTriggers as $removedTrigger) {
            $this->assertNotContains($removedTrigger, $triggers);
        }
    }

    /**
     * @return array
     */
    public function clearExistingScopesPriceListChangeTriggersDataProvider()
    {
        return [
            [
                'websites' => [],
                'accountGroup' => [],
                'account' => ['account.level_1', 'account.level_1.2'],
                'removedTriggers' => ['pl_changed_w1_a1', 'pl_changed_w2_a2']
            ],
            [
                'websites' => [],
                'accountGroup' => ['account_group.group1'],
                'account' => [],
                'removedTriggers' => ['pl_changed_w1_g1']
            ],
            [
                'websites' => [LoadWebsiteData::WEBSITE1],
                'accountGroup' => [],
                'account' => [],
                'removedTriggers' => ['pl_changed_w1']
            ],

        ];
    }

    public function testDeleteAll()
    {
        $this->assertNotEmpty($this->getRepository()->findAll());
        $this->getRepository()->deleteAll();
        $this->assertEmpty($this->getRepository()->findAll());
    }

    public function testGenerateAccountsTriggersByPriceList()
    {
        $this->getRepository()->deleteAll();
        $this->assertEmpty($this->getRepository()->findAll());

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $this->getRepository()->generateAccountsTriggersByPriceList($priceList, $this->insertFromSelectQueryExecutor);

        /** @var PriceListChangeTrigger[] $triggers */
        $triggers = $this->getRepository()->findAll();
        $this->assertNotEmpty($triggers);

        $priceListsToAccounts = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:PriceListToAccount')
            ->getRepository('OroB2BPricingBundle:PriceListToAccount')
            ->findBy(['priceList' => $priceList]);

        $priceListAccountsIds = array_map(
            function (PriceListToAccount $priceListToAccounts) {
                return $priceListToAccounts->getAccount()->getId();
            },
            $priceListsToAccounts
        );

        foreach ($triggers as $trigger) {
            $this->assertContains($trigger->getAccount()->getId(), $priceListAccountsIds);
        }
    }

    public function testGenerateAccountGroupsTriggersByPriceList()
    {
        $this->getRepository()->deleteAll();
        $this->assertEmpty($this->getRepository()->findAll());

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $this->getRepository()
            ->generateAccountGroupsTriggersByPriceList($priceList, $this->insertFromSelectQueryExecutor);

        /** @var PriceListChangeTrigger[] $triggers */
        $triggers = $this->getRepository()->findAll();
        $this->assertNotEmpty($triggers);

        $priceListsToAccountGroups = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:PriceListToAccountGroup')
            ->getRepository('OroB2BPricingBundle:PriceListToAccountGroup')
            ->findBy(['priceList' => $priceList]);

        $priceListAccountGroupsIds = array_map(
            function (PriceListToAccountGroup $priceListToAccounts) {
                return $priceListToAccounts->getAccountGroup()->getId();
            },
            $priceListsToAccountGroups
        );

        foreach ($triggers as $trigger) {
            $this->assertContains($trigger->getAccountGroup()->getId(), $priceListAccountGroupsIds);
        }
    }

    public function testGenerateWebsitesTriggersByPriceList()
    {
        $this->getRepository()->deleteAll();
        $this->assertEmpty($this->getRepository()->findAll());
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $this->getRepository()
            ->generateWebsitesTriggersByPriceList($priceList, $this->insertFromSelectQueryExecutor);

        /** @var PriceListChangeTrigger[] $triggers */
        $triggers = $this->getRepository()->findAll();
        $this->assertNotEmpty($triggers);

        $priceListsToWebsites = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:PriceListToWebsite')
            ->getRepository('OroB2BPricingBundle:PriceListToWebsite')
            ->findBy(['priceList' => $priceList]);

        $priceListWebsitesIds = array_map(
            function (PriceListToWebsite $priceListsToWebsite) {
                return $priceListsToWebsite->getWebsite()->getId();
            },
            $priceListsToWebsites
        );

        foreach ($triggers as $trigger) {
            $this->assertContains($trigger->getWebsite()->getId(), $priceListWebsitesIds);
        }
    }

    /**
     * @return PriceListChangeTriggerRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListChangeTrigger');
    }
}
