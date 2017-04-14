<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductPriceTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadCombinedProductPrices::class
        ]);
    }

    public function testPriceProduct()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_frontend_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $productFirst = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var CombinedProductPrice $expectedPriceFirst */
        $expectedPriceFirst = $this->getReference(LoadCombinedProductPrices::PRICE_PRODUCT_7);
        $productSecond = $this->getReference(LoadProductData::PRODUCT_2);
        /** @var CombinedProductPrice $expectedPriceSecond */
        $expectedPriceSecond = $this->getReference(LoadCombinedProductPrices::PRICE_PRODUCT_8);
        $firstProduct = $crawler->filter('[data-row-id=' . $productFirst->getId() . ']')->html();
        $this->assertContains($expectedPriceFirst->getPrice()->getValue(), $firstProduct);
        $secondProduct = $crawler->filter('[data-row-id=' . $productSecond->getId() . ']')->html();
        $this->assertContains($expectedPriceSecond->getPrice()->getValue(), $secondProduct);
    }
}
