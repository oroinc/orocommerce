<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Intl\Currencies;

class AjaxPriceListControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadPriceLists::class]);
    }

    public function testDefaultAction()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_pricing_price_list_default', ['id' => $priceList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = self::jsonToArray($result->getContent());

        $this->assertArrayHasKey('successful', $data);
        $this->assertTrue($data['successful']);

        $defaultPriceLists = $this->getContainer()->get('doctrine')
            ->getRepository(PriceList::class)
            ->findBy(['default' => true]);

        $this->assertEquals([$priceList], $defaultPriceLists);
    }

    public function testGetPriceListCurrencyListAction()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $this->client->request(
            'GET',
            $this->getUrl('oro_pricing_price_list_currency_list', ['id' => $priceList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = self::jsonToArray($result->getContent());

        $this->assertEquals($priceList->getCurrencies(), array_keys($data));
        $this->assertEquals(
            array_map(
                function ($currencyCode) {
                    return Currencies::getName($currencyCode);
                },
                $priceList->getCurrencies()
            ),
            array_values($data)
        );
    }
}
