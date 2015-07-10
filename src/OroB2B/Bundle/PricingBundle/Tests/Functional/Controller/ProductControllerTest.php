<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListRepository;

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
        $defaultPriceList = $repository->getDefault();

        $this->assertContains(
            $defaultPriceList->getName(),
            $crawler->filter('.default-price-list-choice')->html()
        );

        $this->assertContains(
            $this->getContainer()->get('translator')->trans('orob2b.pricing.productprice.show_tier_prices.label'),
            $crawler->filter('.show-tier-prices-choice')->html()
        );
    }
}
