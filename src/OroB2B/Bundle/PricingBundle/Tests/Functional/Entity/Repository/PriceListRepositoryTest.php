<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

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
