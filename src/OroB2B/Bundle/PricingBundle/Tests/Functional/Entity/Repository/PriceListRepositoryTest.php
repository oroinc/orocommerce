<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

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
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testDefaultState()
    {
        $this->assertEquals([$this->getReference('price_list_3')], $this->getDefaultPriceList());

        $repository = $this->getRepository();

        $repository->dropDefaults();
        $this->assertEquals([], $this->getDefaultPriceList());

        /** @var PriceList $priceList1 */
        $priceList1 = $this->getReference('price_list_1');
        $repository->setDefault($priceList1);
        $this->assertEquals([$priceList1], $this->getDefaultPriceList());

        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference('price_list_2');
        $repository->setDefault($priceList2);
        $this->assertEquals([$priceList2], $this->getDefaultPriceList());

        $repository->dropDefaults();
        $this->assertEquals([], $this->getDefaultPriceList());
    }

    /**
     * @return array|PriceList[]
     */
    public function getDefaultPriceList()
    {
        return $this->getRepository()->findBy(['default' => true]);
    }

    /**
     * @return PriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceList');
    }
}
