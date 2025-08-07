<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadFrontendCategoryProductData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\ProductBundle\Provider\ProductAutocompleteProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductAutocompleteProviderTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?bool $initialAllowPartialSearch;
    private ProductAutocompleteProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $container = self::getContainer();
        $container->get('oro_website_search.indexer')->resetIndex();

        $this->loadFixtures([
            LoadLocalizationData::class,
            LoadFrontendCategoryProductData::class,
            LoadCombinedPriceLists::class,
        ]);

        $configManager = self::getConfigManager();
        $this->initialAllowPartialSearch = $configManager->get('oro_product.allow_partial_product_search');
        $configManager->set('oro_product.allow_partial_product_search', true);
        $configManager->flush();

        $user = $container->get('oro_customer_user.manager')->findUserByEmail(LoadCustomerUserData::AUTH_USER);
        $token = new UsernamePasswordOrganizationToken($user, 'main', $user->getOrganization());

        $container->get('security.token_storage')->setToken($token);
        $container->get('oro_frontend.request.frontend_helper')->emulateFrontendRequest();

        $this->provider = $container->get('oro_product.provider.product_autocomplete');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_product.allow_partial_product_search', $this->initialAllowPartialSearch);
        $configManager->flush();

        $container = self::getContainer();
        $container->get('oro_frontend.request.frontend_helper')->resetRequestEmulation();
        $container->get('security.token_storage')->setToken(null);
    }

    public function testGetAutocompleteData(): void
    {
        $data = $this->provider->getAutocompleteData('продукт');

        $this->assertArrayHasKey('total_count', $data);
        $this->assertEquals(2, $data['total_count']);

        $product7 = $this->getReference(LoadProductData::PRODUCT_7);
        $product9 = $this->getReference(LoadProductData::PRODUCT_9);

        $this->assertArrayHasKey('products', $data);
        $this->assertEquals(
            [
                [
                    'sku' => LoadProductData::PRODUCT_9,
                    'name' => 'продукт-9.names.default',
                    'image' => '/media/cache/resolve/product_small/bundles/oroproduct/images/no_image.png',
                    'imageWebp' => '/media/cache/resolve/product_small/bundles/oroproduct/images/no_image.png.webp',
                    'inventory_status' => 'in_stock',
                    'id' => $product9->getId(),
                    'url' => '/product/view/' . $product9->getId(),
                    'default_image' => '/media/cache/resolve/product_small/bundles/oroproduct/images/no_image.png',
                    'inventory_status_label' => 'In Stock',
                ],
                [
                    'sku' => LoadProductData::PRODUCT_7,
                    'name' => 'продукт-7.names.default',
                    'image' => '/media/cache/resolve/product_small/bundles/oroproduct/images/no_image.png',
                    'imageWebp' => '/media/cache/resolve/product_small/bundles/oroproduct/images/no_image.png.webp',
                    'inventory_status' => 'in_stock',
                    'id' => $product7->getId(),
                    'url' => '/product/view/' . $product7->getId(),
                    'default_image' => '/media/cache/resolve/product_small/bundles/oroproduct/images/no_image.png',
                    'inventory_status_label' => 'In Stock',
                ],
            ],
            $data['products']
        );

        $categoryId = $product7->getCategory()->getId();
        $this->assertArrayHasKey('categories', $data);
        $this->assertEquals(
            [
                [
                    'id' => $categoryId,
                    'url' => '/product/search?search=' . urlencode('продукт') . '&categoryId=' . $categoryId,
                    'tree' => [
                        'category_1',
                        'category_1_5',
                        'category_1_5_6',
                        'category_1_5_6_7',
                    ],
                    'count' => 1,
                ],
            ],
            $data['categories']
        );
    }
}
