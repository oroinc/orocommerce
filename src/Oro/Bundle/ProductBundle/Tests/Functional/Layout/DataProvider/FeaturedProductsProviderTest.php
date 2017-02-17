<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Layout\DataProvider\FeaturedProductsProvider;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FeaturedProductsProviderTest extends WebTestCase
{
    /** @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $productRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadProductDemoData::class
        ]);
        $this->productRepository = $this->getContainer()
            ->get('doctrine')
            ->getRepository(Product::class);
    }

    /**
     * @return Product[]
     */
    public function testGetAll()
    {
        $featuredProductsProvider = new FeaturedProductsProvider(
            $this->productRepository,
            $this->createMock(ProductManager::class)
        );
        $featuredProducts = $featuredProductsProvider->getAll();
        $this->assertCount(10, $featuredProducts);

        return $featuredProducts;
    }

    /**
     * @param array $featuredProducts
     *
     * @depends testGetAll
     */
    public function testFeaturedProductsOnFrontendRootAfterUpdatingProduct($featuredProducts)
    {
        $product = $featuredProducts[0];
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));
        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_product[sku]'] = '2WE9999';
        $this->client->followRedirects(true);
        $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $crawler = $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        /** @var Product $featuredProduct */
        foreach ($featuredProducts as $featuredProduct) {
            $linksCrawler = $crawler->selectLink($featuredProduct->getName());
            $this->assertGreaterThanOrEqual(1, $linksCrawler->count());
        }
    }
}
