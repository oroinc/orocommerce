<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;

/**
 * @dbIsolation
 */
class PriceListControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\Fixtures\LoadPriceLists']);
    }

    public function testDefaultAction()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_list_default', ['id' => $priceList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('successful', $data);
        $this->assertTrue($data['successful']);

        $defaultPriceLists = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroB2BPricingBundle:PriceList')
            ->findBy(['default' => true]);

        $this->assertEquals([$priceList], $defaultPriceLists);
    }
}
