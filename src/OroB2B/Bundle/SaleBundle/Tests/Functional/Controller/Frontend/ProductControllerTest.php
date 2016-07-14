<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices'
        ]);
    }

    /**
     * @dataProvider viewDataProvider
     * @param $product
     * @param $contains
     */
    public function testView($product, $contains)
    {
        $product = $this->getProduct($product);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('orob2b_product_frontend_product_view', ['id' => $product->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $priceTable = $crawler->filter('.product__qnty');
        $this->assertContains($contains, $priceTable->html());
    }

    /**
     * @return array
     */
    public function viewDataProvider()
    {
        return [
            'unit without prices'=> ['product'=> LoadProductData::PRODUCT_2, 'contains'=> 'Request A Quote'],
            'unit with empty price' => ['product'=> LoadProductData::PRODUCT_4, 'contains'=> '$200.50'],
            'unit with not empty price'=> ['product'=> LoadProductData::PRODUCT_5, 'contains'=> '$0.00']
        ];
    }

    /**
     * @param string $reference
     *
     * @return Product
     */
    protected function getProduct($reference)
    {
        return $this->getReference($reference);
    }
}
