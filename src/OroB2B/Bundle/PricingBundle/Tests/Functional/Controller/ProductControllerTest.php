<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists']);
    }

    public function testSidebar()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_pricing_price_product_sidebar'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        /** @var PriceListRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class')
        );
        /** @var PriceList $defaultPriceList */
        $defaultPriceList = $repository->getDefault();

        $this->assertEquals(
            $defaultPriceList->getId(),
            $crawler->filter('.sidebar-item.default-price-list-choice input[type=hidden]')->attr('value')
        );

        foreach ($crawler->filter('.sidebar-item.currencies input[type=checkbox]')->children() as $checkbox) {
            $this->assertContains($checkbox->attr('value'), $defaultPriceList->getCurrencies());
        }

        $this->assertContains(
            $this->getContainer()->get('translator')->trans('orob2b.pricing.productprice.show_tier_prices.label'),
            $crawler->filter('.sidebar-item.show-tier-prices-choice')->html()
        );
    }

    public function testPriceListFromRequest()
    {
        /** @var PriceListRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class')
        );
        /** @var PriceList $priceList */
        $priceList = $repository->findOneBy([]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals(
            $priceList->getId(),
            $crawler->filter('.sidebar-item.default-price-list-choice input[type=hidden]')->attr('value')
        );
    }

    public function testPriceListCurrenciesFromRequestUnchecked()
    {
        /** @var PriceListRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class')
        );
        /** @var PriceList $priceList */
        $priceList = $repository->findOneBy([]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                    PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY => [],
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-item.currencies input[type=checkbox]')
        );
        $this->assertCount(0, $crawler->filter('.sidebar-item.currencies input[type=checkbox][checked=checked]'));
        $crawler->filter('.sidebar-item.currencies input[type=checkbox]')->each(
            function (Crawler $node) use ($priceList) {
                $this->assertContains($node->attr('value'), $priceList->getCurrencies());
                $this->assertEmpty($node->attr('checked'));
            }
        );
    }

    public function testPriceListCurrenciesFromRequestChecked()
    {
        /** @var PriceListRepository $repository */
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_pricing.entity.price_list.class')
        );
        /** @var PriceList $priceList */
        $priceList = $repository->findOneBy([]);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                    PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY => $priceList->getCurrencies(),
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-item.currencies input[type=checkbox]')
        );
        $this->assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-item.currencies input[type=checkbox][checked=checked]')
        );
        $crawler->filter('.sidebar-item.currencies input[type=checkbox]')->each(
            function (Crawler $node) use ($priceList) {
                $this->assertContains($node->attr('value'), $priceList->getCurrencies());
                $this->assertNotEmpty($node->attr('checked'));
            }
        );
    }

    public function testPriceListCurrenciesFromRequestPartialChecked()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $selectedCurrencies = array_diff($priceList->getCurrencies(), ['EUR']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
                    PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY => $selectedCurrencies,
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertSameSize(
            $priceList->getCurrencies(),
            $crawler->filter('.sidebar-item.currencies input[type=checkbox]')
        );
        $this->assertSameSize(
            $selectedCurrencies,
            $crawler->filter('.sidebar-item.currencies input[type=checkbox][checked=checked]')
        );
        $crawler->filter('.sidebar-item.currencies input[type=checkbox]')->each(
            function (Crawler $node) use ($priceList, $selectedCurrencies) {
                $this->assertContains($node->attr('value'), $priceList->getCurrencies());

                if (in_array($node->attr('value'), $selectedCurrencies, true)) {
                    $this->assertNotEmpty($node->attr('checked'));
                } else {
                    $this->assertEmpty($node->attr('checked'));
                }
            }
        );
    }

    public function testShowTierPricesChecked()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::TIER_PRICES_KEY => true,
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals(
            'checked',
            $crawler->filter('.sidebar-item.show-tier-prices-choice input[type=checkbox]')->attr('checked')
        );
    }

    public function testShowTierPricesNotChecked()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'orob2b_pricing_price_product_sidebar',
                [
                    PriceListRequestHandler::TIER_PRICES_KEY => false,
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals(
            '',
            $crawler->filter('.sidebar-item.show-tier-prices-choice input[type=checkbox]')->attr('checked')
        );
    }
}
