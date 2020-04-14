<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Symfony\Component\HttpFoundation\Response;

class PriceListDeleteOperationTest extends ActionTestCase
{
    protected function setUp(): void
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
            PriceList::class,
            'oro_pricing_price_list_index'
        );

        $crawler = $this->client->request('GET', $this->getUrl('oro_pricing_price_list_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, Response::HTTP_OK);
        static::assertStringContainsString('Price List deleted', $crawler->html());
    }

    public function testDeleteDefault()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getRepository()->getDefault();

        $this->client->followRedirects(true);

        $this->assertExecuteOperation(
            'DELETE',
            $priceList->getId(),
            PriceList::class,
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            Response::HTTP_FORBIDDEN
        );

        $this->assertEquals(
            [
                'success' => false,
                'message' => 'Operation "DELETE" execution is forbidden!',
                'messages' => [],
                'refreshGrid' => null,
                'pageReload' => true
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
