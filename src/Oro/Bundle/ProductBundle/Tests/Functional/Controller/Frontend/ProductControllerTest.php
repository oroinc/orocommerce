<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\ProductBundle\Controller\Frontend\ProductController;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Translator $translator
     */
    protected $translator;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadFrontendProductData::class,
            LoadCombinedPriceLists::class,
        ]);

        $this->translator = $this->getContainer()->get('translator');
    }

    public function testIndexAction()
    {
        $this->client->request('GET', $this->getUrl('oro_product_frontend_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        $this->assertNotEmpty($content);
        $this->assertContains(LoadProductData::PRODUCT_1, $content);
        $this->assertContains(LoadProductData::PRODUCT_2, $content);
        $this->assertContains(LoadProductData::PRODUCT_3, $content);
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
        $this->assertArrayNotHasKey('image', $result['data'][0]);

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
        $this->assertContains($product->getSku(), $result->getContent());
        $this->assertContains($product->getDefaultName()->getString(), $result->getContent());

        $this->assertContains(
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
