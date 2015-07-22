<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

/**
 * @dbIsolation
 */
class PriceListControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testDelete()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_pricing_delete_price_list', ['id' => $priceList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }

    public function testDeleteDefault()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getRepository()->getDefault();

        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_pricing_delete_price_list', ['id' => $priceList->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 403);
    }

    /**
     * @return PriceListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BPricingBundle:PriceList');
    }
}
