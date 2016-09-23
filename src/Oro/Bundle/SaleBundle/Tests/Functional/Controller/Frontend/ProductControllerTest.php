<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions',
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices'
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
            $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
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
