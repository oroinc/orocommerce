<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

/**
 * @dbIsolation
 */
class PriceListDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testDelete()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->assertDeleteOperation(
            $priceList->getId(),
            'orob2b_pricing.entity.price_list.class',
            'orob2b_pricing_price_list_index'
        );
    }

    public function testDeleteDefault()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getRepository()->getDefault();

        $this->client->followRedirects(true);

        $this->assertExecuteOperation(
            'DELETE',
            $priceList->getId(),
            $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class'),
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            404
        );

        $this->assertEquals(
            [
                'success' => false,
                'message' => 'Operation with name "DELETE" not found',
                'messages' => [],
                'refreshGrid' => null,
                'flashMessages' => []
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }

    /**
     * @return PriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceList');
    }
}
