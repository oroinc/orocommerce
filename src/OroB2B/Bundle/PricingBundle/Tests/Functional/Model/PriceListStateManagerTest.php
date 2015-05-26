<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Model;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

/**
 * @dbIsolation
 */
class PriceListStateManagerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\Fixtures\LoadPriceLists']);
    }

    public function testApplyDefault()
    {
        $this->assertEquals([$this->getReference('price_list_3')], $this->getDefaultPriceList());

        $manager = $this->getContainer()->get('orob2b_pricing.model.price_list_state_manager');

        /** @var PriceList $priceList1 */
        $priceList1 = $this->getReference('price_list_1');
        $manager->applyDefault($priceList1);
        $this->assertEquals([$priceList1], $this->getDefaultPriceList());

        /** @var PriceList $priceList2 */
        $priceList2 = $this->getReference('price_list_2');
        $manager->applyDefault($priceList2);
        $this->assertEquals([$priceList2], $this->getDefaultPriceList());
    }

    /**
     * @return array|PriceList[]
     */
    public function getDefaultPriceList()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:PriceList')
            ->findBy(['default' => true]);
    }
}
