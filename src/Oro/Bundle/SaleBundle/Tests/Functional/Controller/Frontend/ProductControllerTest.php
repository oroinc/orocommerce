<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadFrontendProductData::class,
            LoadProductUnitPrecisions::class,
            LoadCombinedProductPrices::class,
        ]);
    }

    /**
     * @dataProvider viewDataProvider
     */
    public function testView(string $product, string $contains)
    {
        /** @var Product $product */
        $product = $this->getReference($product);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        $priceTable = $crawler->filter('.product-prices__table');
        self::assertStringContainsString($contains, $priceTable->text());
    }

    public function viewDataProvider(): array
    {
        return [
            'unit without prices'       => ['product' => LoadProductData::PRODUCT_2, 'contains' => 'Get Quote'],
            'unit with empty price'     => ['product' => LoadProductData::PRODUCT_6, 'contains' => '$200.50'],
            'unit with not empty price' => ['product' => LoadProductData::PRODUCT_7, 'contains' => '$0.00']
        ];
    }
}
