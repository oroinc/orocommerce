<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Operation;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

class PriceListDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(['Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testDelete()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->assertDeleteOperation(
            $priceList->getId(),
            'oro_pricing.entity.price_list.class',
            'oro_pricing_price_list_index'
        );

        $crawler = $this->client->request('GET', $this->getUrl('oro_pricing_price_list_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
        $this->assertContains('Price List deleted', $crawler->html());
    }

    public function testDeleteDefault()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getRepository()->getDefault();

        $this->client->followRedirects(true);

        $this->assertExecuteOperation(
            'DELETE',
            $priceList->getId(),
            $this->getContainer()->getParameter('oro_pricing.entity.price_list.class'),
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            Response::HTTP_FORBIDDEN
        );

        $this->assertEquals(
            [
                'success' => false,
                'message' => '',
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
        return $this->getContainer()->get('doctrine')->getRepository('OroPricingBundle:PriceList');
    }
}
