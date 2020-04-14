<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributePriceLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Intl\Currencies;

class PriceAttributePriceListControllerTest extends WebTestCase
{
    const PRICE_ATTRIBUTE_PRICE_LIST_NAME = 'MSRP';
    const PRICE_ATTRIBUTE_PRICE_LIST_FIELD_NAME = 'msrp';
    const PRICE_ATTRIBUTE_PRICE_LIST_NAME_EDIT = 'MAP';
    const PRICE_ATTRIBUTE_PRICE_LIST_FIELD_NAME_EDIT = 'map';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadPriceAttributePriceLists::class]);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_pricing_price_attribute_price_list_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('pricing-price-attribute-price-list-grid', $crawler->html());

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
        static::assertStringContainsString($priceAttributePriceList->getName(), $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_pricing_price_attribute_price_list_create'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save and Close')->form([
            'oro_pricing_price_attribute_price_list[name]' => self::PRICE_ATTRIBUTE_PRICE_LIST_NAME,
            'oro_pricing_price_attribute_price_list[fieldName]' => self::PRICE_ATTRIBUTE_PRICE_LIST_FIELD_NAME,
        ]);

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        static::assertStringContainsString('Price Attribute has been saved', $html);

        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroPricingBundle:PriceAttributePriceList')
            ->getRepository('OroPricingBundle:PriceAttributePriceList')
            ->findOneBy(['name' => self::PRICE_ATTRIBUTE_PRICE_LIST_NAME]);
        $this->assertNotEmpty($priceAttributePriceList);
    }

    public function testView()
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $this->getReference('price_attribute_price_list_4');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_pricing_price_attribute_price_list_view', ['id' => $priceAttributePriceList->getId()])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString($priceAttributePriceList->getName(), $crawler->html());
    }

    public function testUpdate()
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $this->getReference('price_attribute_price_list_3');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_pricing_price_attribute_price_list_update',
                ['id' => $priceAttributePriceList->getId()]
            )
        );

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_pricing_price_attribute_price_list[name]' => self::PRICE_ATTRIBUTE_PRICE_LIST_NAME_EDIT,
                'oro_pricing_price_attribute_price_list[fieldName]'
                    => self::PRICE_ATTRIBUTE_PRICE_LIST_FIELD_NAME_EDIT,
                'oro_pricing_price_attribute_price_list[currencies]' => 'CAD',
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString(self::PRICE_ATTRIBUTE_PRICE_LIST_NAME_EDIT, $crawler->html());
        static::assertStringContainsString(Currencies::getName('CAD'), $crawler->html());
    }

    public function testFieldNameValidator()
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $this->getReference('price_attribute_price_list_3');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_pricing_price_attribute_price_list_update',
                ['id' => $priceAttributePriceList->getId()]
            )
        );

        $form = $crawler->selectButton('Save and Close')->form(
            [
                'oro_pricing_price_attribute_price_list[name]' => self::PRICE_ATTRIBUTE_PRICE_LIST_NAME_EDIT,
                'oro_pricing_price_attribute_price_list[fieldName]' => '3',
                'oro_pricing_price_attribute_price_list[currencies]' => 'CAD',
            ]
        );

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        static::assertStringContainsString(
            'Field Name cannot contain special chars or spaces, and must contain at least one letter',
            $crawler->html()
        );
    }

    public function testInfo()
    {
        /** @var PriceAttributePriceList $priceAttributePriceList */
        $priceAttributePriceList = $this->getReference('price_attribute_price_list_3');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_pricing_price_attribute_price_list_info',
                ['id' => $priceAttributePriceList->getId()]
            ),
            ['_widgetContainer' => 'widget']
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString(self::PRICE_ATTRIBUTE_PRICE_LIST_NAME_EDIT, $crawler->html());
        static::assertStringContainsString(Currencies::getName('CAD'), $crawler->html());
    }
}
