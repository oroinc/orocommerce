<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributePriceLists;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Intl\Intl;

/**
 * @dbIsolation
 */
class PriceAttributePriceListControllerTest extends WebTestCase
{
    const PRICE_ATTRIBUTE_PRICE_LIST_NAME = 'MSRP';
    const PRICE_ATTRIBUTE_PRICE_LIST_NAME_EDIT = 'MAP';
    const CURRENCY = 'USD';

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadPriceAttributePriceLists::class]);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_pricing_price_attribute_price_list_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('pricing-price-attribute-price-list-grid', $crawler->html());

        $this->checkContainsPriceAttributePriceList($crawler, 'price_attribute_price_list_1');
        $this->checkContainsPriceAttributePriceList($crawler, 'price_attribute_price_list_2');
        $this->checkContainsPriceAttributePriceList($crawler, 'price_attribute_price_list_3');
        $this->checkContainsPriceAttributePriceList($crawler, 'price_attribute_price_list_4');
        $this->checkContainsPriceAttributePriceList($crawler, 'price_attribute_price_list_5');
    }

    /**
     * @param Crawler $crawler
     * @param string $priceAttributePriceListReference
     */
    protected function checkContainsPriceAttributePriceList(Crawler $crawler, $priceAttributePriceListReference)
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $this->getReference($priceAttributePriceListReference);
        $this->assertContains($priceAttributePriceList->getName(), $crawler->html());
    }
    
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_pricing_price_attribute_price_list_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_pricing_price_attribute_price_list[name]' => self::PRICE_ATTRIBUTE_PRICE_LIST_NAME,
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertContains('Price Attribute has been saved', $html);

        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BPricingBundle:PriceAttributePriceList')
            ->getRepository('OroB2BPricingBundle:PriceAttributePriceList')
            ->findOneBy(['name' => self::PRICE_ATTRIBUTE_PRICE_LIST_NAME]);
        $this->assertNotEmpty($priceAttributePriceList);
    }

    public function testView()
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $this->getReference('price_attribute_price_list_4');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_pricing_price_attribute_price_list_view', ['id' => $priceAttributePriceList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains($priceAttributePriceList->getName(), $crawler->html());
    }

    public function testUpdate()
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $this->getReference('price_attribute_price_list_3');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_attribute_price_list_update',
                ['id' => $priceAttributePriceList->getId()]
            )
        );

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'orob2b_pricing_price_attribute_price_list[name]' => self::PRICE_ATTRIBUTE_PRICE_LIST_NAME_EDIT,
                'orob2b_pricing_price_attribute_price_list[currencies]' => self::CURRENCY,
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertContains(self::PRICE_ATTRIBUTE_PRICE_LIST_NAME_EDIT, $crawler->html());
        $this->assertContains(Intl::getCurrencyBundle()->getCurrencyName(self::CURRENCY), $crawler->html());
    }

    public function testInfo()
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $this->getReference('price_attribute_price_list_3');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_attribute_price_list_info',
                ['id' => $priceAttributePriceList->getId()]
            ),
            ['_widgetContainer' => 'widget']
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(self::PRICE_ATTRIBUTE_PRICE_LIST_NAME_EDIT, $crawler->html());
        $this->assertContains(Intl::getCurrencyBundle()->getCurrencyName(self::CURRENCY), $crawler->html());
    }
}
