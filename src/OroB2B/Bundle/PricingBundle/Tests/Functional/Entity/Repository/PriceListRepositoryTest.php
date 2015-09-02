<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class PriceListRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testDefaultState()
    {
        $repository = $this->getRepository();

        /** @var PriceList $priceList1 */
        $priceList1 = $this->getReference('price_list_1');
        $repository->setDefault($priceList1);
        $this->assertEquals($priceList1->getId(), $this->getDefaultPriceList()->getId());

        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference('price_list_2');
        $repository->setDefault($priceList2);
        $this->assertEquals($priceList2->getId(), $this->getDefaultPriceList()->getId());
    }

    public function testGetDefault()
    {
        $this->assertEquals($this->getDefaultPriceList()->getId(), $this->getRepository()->getDefault()->getId());
    }

    /**
     * @return PriceList
     */
    public function getDefaultPriceList()
    {
        $defaultPriceLists = $this->getRepository()->findBy(['default' => true]);

        $this->assertCount(1, $defaultPriceLists);

        return reset($defaultPriceLists);
    }

    public function testAccountPriceList()
    {
        /** @var Account $account */
        $account = $this->getReference('account.orphan');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_3');

        $this->assertTrue($priceList->getAccounts()->contains($account));

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getPriceListByAccount($account)->getId()
        );
        $this->getRepository()->setPriceListToAccount($account, null);
        $this->getManager()->flush();

        /** @var PriceList $newPriceList */
        $newPriceList = $this->getReference('price_list_2');

        $this->getRepository()->setPriceListToAccount($account, $newPriceList);
        $this->getManager()->flush();

        $this->assertFalse($priceList->getAccounts()->contains($account));
        $this->assertTrue($newPriceList->getAccounts()->contains($account));

        $this->assertEquals(
            $newPriceList->getId(),
            $this->getRepository()->getPriceListByAccount($account)->getId()
        );
    }

    public function testAccountGroupPriceList()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->assertTrue($priceList->getAccountGroups()->contains($accountGroup));

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getPriceListByAccountGroup($accountGroup)->getId()
        );

        /** @var PriceList $newPriceList */
        $newPriceList = $this->getReference('price_list_2');

        $this->getRepository()->setPriceListToAccountGroup($accountGroup, $newPriceList);

        $this->getManager()->flush();

        $this->assertFalse($priceList->getAccountGroups()->contains($accountGroup));
        $this->assertTrue($newPriceList->getAccountGroups()->contains($accountGroup));

        $this->assertEquals(
            $newPriceList->getId(),
            $this->getRepository()->getPriceListByAccountGroup($accountGroup)->getId()
        );
    }

    public function testWebsitePriceList()
    {
        /** @var Website $website */
        $website = $this->getReference('US');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->assertTrue($priceList->getWebsites()->contains($website));

        $this->assertEquals(
            $priceList->getId(),
            $this->getRepository()->getPriceListByWebsite($website)->getId()
        );

        /** @var PriceList $newPriceList */
        $newPriceList = $this->getReference('price_list_2');

        $this->getRepository()->setPriceListToWebsite($website, $newPriceList);

        $this->getManager()->flush();

        $this->assertFalse($priceList->getWebsites()->contains($website));
        $this->assertTrue($newPriceList->getWebsites()->contains($website));

        $this->assertEquals(
            $newPriceList->getId(),
            $this->getRepository()->getPriceListByWebsite($website)->getId()
        );
    }

    /**
     * @return PriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceList');
    }

    /**
     * @return ObjectManager
     */
    protected function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BPricingBundle:PriceList');
    }
}
