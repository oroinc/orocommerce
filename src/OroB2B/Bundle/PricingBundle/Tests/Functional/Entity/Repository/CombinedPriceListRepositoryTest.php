<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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

    public function testAccountPriceList()
    {
        $this->markTestIncomplete();
        /** @var Account $account */
        $account = $this->getReference('account.orphan');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('combined_price_list_2');

        $priceListToAccount = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:CombinedPriceListToAccount')
            ->findOneBy(['account' => $account, 'priceList' => $priceList]);
        $this->assertNotNull($priceListToAccount);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getCombinedPriceListByAccount($account)->getId()
        );
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:CombinedPriceListToAccount');
        $em->remove($priceListToAccount);
        $em->flush();
        $this->assertNull($this->getRepository()->getCombinedPriceListByAccount($account));
    }

    public function testAccountGroupPriceList()
    {
        $this->markTestIncomplete();
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('combined_price_list_1');

        $priceListToAccountGroup = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:CombinedPriceListToAccountGroup')
            ->findOneBy(['accountGroup' => $accountGroup, 'priceList' => $priceList]);
        $this->assertNotNull($priceListToAccountGroup);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getCombinedPriceListByAccountGroup($accountGroup)->getId()
        );
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:CombinedPriceListToAccountGroup');
        $em->remove($priceListToAccountGroup);
        $em->flush();
        $this->assertNull($this->getRepository()->getCombinedPriceListByAccountGroup($accountGroup));
    }

    public function testWebsitePriceList()
    {
        $this->markTestIncomplete();
        /** @var Website $website */
        $website = $this->getReference('US');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('combined_price_list_1');

        $priceListToAccountGroup = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:CombinedPriceListToWebsite')
            ->findOneBy(['website' => $website, 'priceList' => $priceList]);
        $this->assertNotNull($priceListToAccountGroup);

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getCombinedPriceListByWebsite($website)->getId()
        );
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:CombinedPriceListToWebsite');
        $em->remove($priceListToAccountGroup);
        $em->flush();
        $this->assertNull($this->getRepository()->getCombinedPriceListByWebsite($website));
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
