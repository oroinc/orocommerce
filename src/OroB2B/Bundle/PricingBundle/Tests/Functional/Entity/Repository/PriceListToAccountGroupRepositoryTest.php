<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class PriceListToAccountGroupRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
                'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListFallbackSettings',
            ]
        );
    }

    public function testFindByPrimaryKey()
    {
        $repository = $this->getRepository();

        /** @var PriceListToAccountGroup $actualPriceListToAccountGroup */
        $actualPriceListToAccountGroup = $repository->findOneBy([]);

        $expectedPriceListToAccountGroup = $repository->findByPrimaryKey(
            $actualPriceListToAccountGroup->getPriceList(),
            $actualPriceListToAccountGroup->getAccountGroup(),
            $actualPriceListToAccountGroup->getWebsite()
        );

        $this->assertEquals(
            spl_object_hash($expectedPriceListToAccountGroup),
            spl_object_hash($actualPriceListToAccountGroup)
        );
    }

    /**
     * @dataProvider getPriceListDataProvider
     * @param string $accountGroup
     * @param string $website
     * @param array $expectedPriceLists
     */
    public function testGetPriceLists($accountGroup, $website, array $expectedPriceLists)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroup);
        /** @var Website $website */
        $website = $this->getReference($website);

        $actualPriceListsToAccountGroup = $this->getRepository()->getPriceLists($accountGroup, $website);

        $actualPriceLists = array_map(
            function (PriceListToAccountGroup $priceListToAccountGroup) {
                return $priceListToAccountGroup->getPriceList()->getName();
            },
            $actualPriceListsToAccountGroup
        );

        $this->assertEquals($expectedPriceLists, $actualPriceLists);
    }

    /**
     * @return array
     */
    public function getPriceListDataProvider()
    {
        return [
            [
                'account' => 'account_group.group1',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList5',
                    'priceList1'
                ]
            ],
            [
                'account' => 'account_group.group2',
                'website' => 'US',
                'expectedPriceLists' => [
                    'priceList4'
                ]
            ],
            [
                'account' => 'account_group.group3',
                'website' => 'Canada',
                'expectedPriceLists' => [
                    'priceList5'
                ]
            ],
        ];
    }

    /**
     * @dataProvider getPriceListIteratorDataProvider
     * @param string $website
     * @param int|null$fallback
     * @param array $expectedAccounts
     */
    public function testGetAccountGroupIteratorByFallback($website, $fallback, $expectedAccounts)
    {
        /** @var $website Website */
        $website = $this->getReference($website);

        $iterator = $this->getRepository()
            ->getAccountGroupIteratorByDefaultFallback($website, $fallback);

        $actualSiteMap = [];
        foreach ($iterator as $accountGroup) {
            $actualSiteMap[] = $accountGroup->getName();
        }
        $this->assertSame($expectedAccounts, $actualSiteMap);
    }

    /**
     * @return array
     */
    public function getPriceListIteratorDataProvider()
    {
        return [
            'with fallback' => [
                'website' => 'US',
                'fallback' => PriceListAccountGroupFallback::WEBSITE,
                'expectedAccounts' => ['account_group.group1']
            ],
            'without fallback' => [
                'website' => 'US',
                'fallback' => null,
                'expectedAccounts' => [
                    'account_group.group1',
                    'account_group.group2'
                ]
            ],
        ];
    }

    public function testGetWebsiteIdsByAccountGroup()
    {
        /** @var AccountGroup $group */
        $group = $this->getReference('account_group.group1');
        /** @var Website $website */
        $website = $this->getReference('US');
        $ids = $this->getRepository()->getWebsiteIdsByAccountGroup($group);
        $this->assertCount(1, $ids);
        $this->assertEquals($website->getId(), $ids[0]);
    }

    /**
     * @return PriceListToAccountGroupRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceListToAccountGroup');
    }

    public function testDelete()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');
        /** @var Website $website */
        $website = $this->getReference('US');
        $this->assertCount(5, $this->getRepository()->findAll());
        $this->assertCount(3, $this->getRepository()->findBy(['accountGroup' => $accountGroup, 'website' => $website]));
        $this->getRepository()->delete($accountGroup, $website);
        $this->assertCount(2, $this->getRepository()->findAll());
        $this->assertCount(0, $this->getRepository()->findBy(['accountGroup' => $accountGroup, 'website' => $website]));
    }
}
