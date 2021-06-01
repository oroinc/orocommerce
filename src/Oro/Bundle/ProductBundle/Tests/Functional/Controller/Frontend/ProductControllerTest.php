<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\ProductBundle\Controller\Frontend\ProductController;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Routing\RequestContext;

/**
 * @dbIsolationPerTest
 */
class ProductControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Translator $translator
     */
    protected $translator;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->getContainer()
            ->get('oro_website_search.indexer')
            ->resetIndex();

        $this->loadFixtures([
            LoadLocalizationData::class,
            LoadFrontendProductData::class,
            LoadCombinedPriceLists::class,
        ]);

        $this->translator = $this->getContainer()->get('translator');
    }

    public function testIndexAction()
    {
        $this->client->request('GET', $this->getUrl('oro_product_frontend_product_search'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertNotEmpty($content);
        static::assertStringContainsString(LoadProductData::PRODUCT_1, $content);
        static::assertStringContainsString(LoadProductData::PRODUCT_2, $content);
        static::assertStringContainsString(LoadProductData::PRODUCT_3, $content);
    }

    public function testSearchAction(): void
    {
        $this->client->request('GET', $this->getUrl('oro_product_frontend_product_search'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertNotEmpty($content);
        static::assertStringContainsString(LoadProductData::PRODUCT_1, $content);
        static::assertStringContainsString(LoadProductData::PRODUCT_2, $content);
        static::assertStringContainsString(LoadProductData::PRODUCT_3, $content);
    }

    public function testAutocompleteAction(): void
    {
        $key = Configuration::getConfigKeyByName(Configuration::ALLOW_PARTIAL_PRODUCT_SEARCH);

        $configManager = self::getConfigManager('global');
        $originalValue = $configManager->get($key);
        $configManager->set($key, true);
        $configManager->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_product_frontend_product_search_autocomplete'),
            ['search' => 'продукт']
        );

        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);

        $data = \json_decode($response->getContent(), true);

        static::assertArrayHasKey('total_count', $data);
        static::assertEquals(2, $data['total_count']);

        $product7 = $this->getReference(LoadProductData::PRODUCT_7);
        $product9 = $this->getReference(LoadProductData::PRODUCT_9);

        static::assertArrayHasKey('products', $data);
        static::assertArrayHasKey(LoadProductData::PRODUCT_7, $data['products']);
        static::assertEquals(
            [
                'sku' => LoadProductData::PRODUCT_7,
                'name' => 'продукт-7.names.default',
                'image' => '/media/cache/resolve/product_small/bundles/oroproduct/images/no_image.png',
                'inventory_status' => 'in_stock',
                'id' => $product7->getId(),
                'url' => '/product/view/' . $product7->getId(),
                'default_image' => '/media/cache/resolve/product_small/bundles/oroproduct/images/no_image.png',
                'inventory_status_label' => 'In Stock',
            ],
            $data['products'][LoadProductData::PRODUCT_7]
        );
        static::assertArrayHasKey(LoadProductData::PRODUCT_9, $data['products']);
        static::assertEquals(
            [
                'sku' => LoadProductData::PRODUCT_9,
                'name' => 'продукт-9.names.default',
                'image' => '/media/cache/resolve/product_small/bundles/oroproduct/images/no_image.png',
                'inventory_status' => 'in_stock',
                'id' => $product9->getId(),
                'url' => '/product/view/' . $product9->getId(),
                'default_image' => '/media/cache/resolve/product_small/bundles/oroproduct/images/no_image.png',
                'inventory_status_label' => 'In Stock',
            ],
            $data['products'][LoadProductData::PRODUCT_9]
        );

        $configManager->set($key, $originalValue);
        $configManager->flush();
    }

    public function testIndexActionInSubfolder()
    {
        //Emulate subfolder request
        /** @var RequestContext $requestContext */
        $requestContext = static::getContainer()->get('router.request_context');
        $requestContext->setBaseUrl('custom/base/url');

        $this->client->request('GET', static::getUrl('oro_product_frontend_product_index'), [], [], [
            'SCRIPT_NAME' => '/custom/base/url/index.php',
            'SCRIPT_FILENAME' => 'index.php'
        ]);

        /** @var Product $firstProduct */
        $firstProduct = $this->getReference(LoadProductData::PRODUCT_1);
        $images = $firstProduct->getImages();

        $firstProductImage = $this->client->getCrawler()->filter(
            sprintf('img.product-item__preview-image[alt="%s"]', LoadProductData::PRODUCT_1_DEFAULT_NAME)
        );

        $this->assertStringMatchesFormat(
            '%s/product_large/%s/' . $images[0]->getImage()->getId() . '/product-1.jpg',
            $firstProductImage->attr('src')
        );
    }

    public function testIndexDatagridViews()
    {
        // default view is DataGridThemeHelper::VIEW_GRID
        $response = $this->client->requestFrontendGrid(ProductController::GRID_NAME, [], true);
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);

        $response = $this->client->requestFrontendGrid(
            ProductController::GRID_NAME,
            [
                'frontend-product-search-grid[row-view]' => DataGridThemeHelper::VIEW_LIST,
            ],
            true
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);

        $response = $this->client->requestFrontendGrid(
            ProductController::GRID_NAME,
            [
                'frontend-product-search-grid[row-view]' => DataGridThemeHelper::VIEW_GRID,
            ],
            true
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);

        $response = $this->client->requestFrontendGrid(
            ProductController::GRID_NAME,
            [
                'frontend-product-search-grid[row-view]' => DataGridThemeHelper::VIEW_TILES,
            ],
            true
        );

        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);

        // view saves to session so current view is DataGridThemeHelper::VIEW_TILES
        $response = $this->client->requestFrontendGrid(ProductController::GRID_NAME, [], true);
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertArrayHasKey('image', $result['data'][0]);
    }

    public function testFrontendProductGridFilterBySku()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $response = $this->client->requestFrontendGrid(
            'frontend-product-search-grid',
            [
                'frontend-product-search-grid[_filter][sku][type]' => TextFilterType::TYPE_CONTAINS,
                'frontend-product-search-grid[_filter][sku][value]' => $product->getSku(),
            ],
            true
        );
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $result['data']);
        $this->assertEquals($product->getSku(), $result['data'][0]['sku']);
    }

    public function testViewProductWithRequestQuoteAvailable()
    {
        $product = $this->getProduct(LoadProductData::PRODUCT_1);

        $this->assertInstanceOf('Oro\Bundle\ProductBundle\Entity\Product', $product);

        $this->client->request(
            'GET',
            $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString($product->getSku(), $result->getContent());
        static::assertStringContainsString($product->getDefaultName()->getString(), $result->getContent());

        static::assertStringContainsString(
            $this->translator->trans(
                'oro.frontend.product.view.request_a_quote'
            ),
            $result->getContent()
        );
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
