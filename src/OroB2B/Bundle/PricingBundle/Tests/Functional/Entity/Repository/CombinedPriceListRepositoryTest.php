<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class CombinedPriceListRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists']);
    }

    public function testGetPriceListRelations()
    {
        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('2t_3f_1t');

        $relations = $this->getRepository()->getPriceListRelations($priceList);
        $this->assertNotEmpty($relations);
        $this->assertCount(3, $relations);

        $expected = [
            $this->getReference('price_list_2')->getId() => true,
            $this->getReference('price_list_3')->getId() => false,
            $this->getReference('price_list_1')->getId() => true,
        ];
        $actual = [];
        foreach ($relations as $relation) {
            $actual[$relation->getPriceList()->getId()] = $relation->isMergeAllowed();
        }
        $this->assertEquals($expected, $actual);
    }

    public function testAccountPriceList()
    {
        /** @var Account $account */
        $account = $this->getReference('account.level_1.2');

        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('2t_3f_1t');

        /** @var Website $websiteUs */
        $websiteUs = $this->getReference(LoadWebsiteData::WEBSITE1);

        /** @var Website $websiteCa */
        $websiteCa = $this->getReference(LoadWebsiteData::WEBSITE2);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getCombinedPriceListByAccount($account, $websiteUs)->getId()
        );
        $this->assertNull($this->getRepository()->getCombinedPriceListByAccount($account, $websiteCa));
    }

    public function testAccountGroupPriceList()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');

        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('1t_2t_3t');

        /** @var Website $websiteUs */
        $websiteUs = $this->getReference(LoadWebsiteData::WEBSITE1);

        /** @var Website $websiteCa */
        $websiteCa = $this->getReference(LoadWebsiteData::WEBSITE2);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getCombinedPriceListByAccountGroup($accountGroup, $websiteUs)->getId()
        );
        $this->assertNull($this->getRepository()->getCombinedPriceListByAccountGroup($accountGroup, $websiteCa));
    }

    public function testWebsitePriceList()
    {
        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('1t_2t_3t');

        /** @var Website $websiteUs */
        $websiteUs = $this->getReference(LoadWebsiteData::WEBSITE1);

        /** @var Website $websiteCa */
        $websiteCa = $this->getReference(LoadWebsiteData::WEBSITE2);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getCombinedPriceListByWebsite($websiteUs)->getId()
        );
        $this->assertNull($this->getRepository()->getCombinedPriceListByWebsite($websiteCa));
    }

    public function testDeleteUnusedPriceLists()
    {
        $combinedPriceList = new CombinedPriceList();
        $combinedPriceList->setEnabled(true);
        $combinedPriceList->setName('test_cpl');
        $this->getManager()->persist($combinedPriceList);
        $this->getManager()->flush();

        $priceLists = $this->getRepository()->findBy(['name' => 'test_cpl']);
        $this->assertNotEmpty($priceLists);

        $this->getRepository()->deleteUnusedPriceLists($priceLists, null);
        $priceLists = $this->getRepository()->findBy(['name' => 'test_cpl']);
        $this->assertNotEmpty($priceLists);

        $this->getRepository()->deleteUnusedPriceLists([], false);
        $priceLists = $this->getRepository()->findBy(['name' => 'test_cpl']);
        $this->assertNotEmpty($priceLists);

        $this->getRepository()->deleteUnusedPriceLists();
        $priceLists = $this->getRepository()->findBy(['name' => 'test_cpl']);
        $this->assertEmpty($priceLists);
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:CombinedPriceList');
    }

    /**
     * @return ObjectManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BPricingBundle:CombinedPriceList');
    }
}
