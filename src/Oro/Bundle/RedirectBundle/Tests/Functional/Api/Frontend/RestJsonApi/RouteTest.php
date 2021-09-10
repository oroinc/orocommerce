<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi\WebCatalogTreeTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class RouteTest extends WebCatalogTreeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroRedirectBundle/Tests/Functional/Api/Frontend/DataFixtures/route.yml'
        ]);
        $this->switchToWebCatalog();
    }

    protected function postFixtureLoad()
    {
        parent::postFixtureLoad();

        /** @var SlugGenerator $slugGenerator */
        $slugGenerator = self::getContainer()->get('oro_web_catalog.generator.slug_generator');
        $slugGenerator->generate($this->getReference('catalog1_rootNode'));
        $slugGenerator->generate($this->getReference('catalog1_node1'));
        $slugGenerator->generate($this->getReference('catalog1_node2'));
        $slugGenerator->generate($this->getReference('catalog1_node3'));
        $slugGenerator->generate($this->getReference('catalog1_node4'));
        $slugGenerator->generate($this->getReference('catalog1_node5'));
        $this->getEntityManager()->flush();

        /** @var LocalizedFallbackValue $slugPrototype */
        $slugPrototype = $this->getReference('catalog1_node4_slug_prototype');
        $slugPrototype->setString('catalog1_node4_new');
        $slugGenerator->generate($this->getReference('catalog1_node4'), true);
        $this->getEntityManager()->flush();
    }

    private function getId(string $reference): string
    {
        return (string)$this->getReference($reference)->getId();
    }

    private function getRouteId(string $pathIfo): string
    {
        return str_replace('/', ':', $pathIfo);
    }

    public function testTryToGetForNotExistingUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/not-existing')],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetForManagementConsoleUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId($this->getUrl('oro_default'))],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGetForRootPageUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':',
                    'attributes' => [
                        'url'                => '/',
                        'isSlug'             => true,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'system_page',
                        'apiUrl'             => '/api/systempages/oro_frontend_root'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForCustomerUserAddressesUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/customer/user/address')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':customer:user:address',
                    'attributes' => [
                        'url'                => '/customer/user/address/',
                        'isSlug'             => false,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'system_page',
                        'apiUrl'             => '/api/systempages/oro_customer_frontend_customer_user_address_index'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForAllProductsUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/product')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':product',
                    'attributes' => [
                        'url'                => '/product/',
                        'isSlug'             => false,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'products',
                        'apiUrl'             => '/api/products'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForCustomerUserProfileUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/customer/profile')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':customer:profile',
                    'attributes' => [
                        'url'                => '/customer/profile/',
                        'isSlug'             => false,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'customer_user',
                        'apiUrl'             => '/api/customerusers/mine'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForCustomerUserUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/customer/user/view/1')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':customer:user:view:1',
                    'attributes' => [
                        'url'                => '/customer/user/view/1',
                        'isSlug'             => false,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'customer_user',
                        'apiUrl'             => '/api/customerusers/1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForDefaultShoppingListUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/customer/shoppinglist')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':customer:shoppinglist',
                    'attributes' => [
                        'url'                => '/customer/shoppinglist',
                        'isSlug'             => false,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'shopping_list',
                        'apiUrl'             => '/api/shoppinglists/default'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForShoppingListUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/customer/shoppinglist/1')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':customer:shoppinglist:1',
                    'attributes' => [
                        'url'                => '/customer/shoppinglist/1',
                        'isSlug'             => false,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'shopping_list',
                        'apiUrl'             => '/api/shoppinglists/1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetOnlyUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/')],
            ['fields[routes]' => 'url']
        );
        $this->assertEquals(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':',
                    'attributes' => [
                        'url' => '/'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testGetOnlyResourceType()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/')],
            ['fields[routes]' => 'resourceType']
        );
        $this->assertEquals(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':',
                    'attributes' => [
                        'resourceType' => 'system_page'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testGetOnlyApiUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/')],
            ['fields[routes]' => 'apiUrl']
        );
        $this->assertEquals(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':',
                    'attributes' => [
                        'apiUrl' => '/api/systempages/oro_frontend_root'
                    ]
                ]
            ],
            self::jsonToArray($response->getContent())
        );
    }

    public function testGetForProductCollectionRoute()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/catalog1_node1')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':catalog1_node1',
                    'attributes' => [
                        'url'                => '/catalog1_node1',
                        'isSlug'             => true,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'product_collection',
                        'apiUrl'             => '/api/productcollection/' . $this->getId('catalog1_node1_variant')
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForMasterCategoryProductsRoute()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/catalog1_node5')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':catalog1_node5',
                    'attributes' => [
                        'url'                => '/catalog1_node5',
                        'isSlug'             => true,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'master_catalog_category_product_collection',
                        'apiUrl'             => '/api/products?filter%5Bcategory%5D=' . $this->getId('category1')
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForMasterCategoryProductsWithIncludeSubcategoriesRoute()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/catalog1_node2')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':catalog1_node2',
                    'attributes' => [
                        'url'                => '/catalog1_node2',
                        'isSlug'             => true,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'master_catalog_category_product_collection',
                        'apiUrl'             => '/api/products?filter%5BrootCategory%5D%5Bgte%5D='
                            . $this->getId('category1')
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForProductRoute()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/catalog1_node3')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':catalog1_node3',
                    'attributes' => [
                        'url'                => '/catalog1_node3',
                        'isSlug'             => true,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'product',
                        'apiUrl'             => '/api/products/' . $this->getId('product1')
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForLandingPageRoute()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/catalog1_node4_new')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':catalog1_node4_new',
                    'attributes' => [
                        'url'                => '/catalog1_node4_new',
                        'isSlug'             => true,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'landing_page',
                        'apiUrl'             => '/api/landingpages/' . $this->getId('landing_page1')
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForRedirectRouteSlug()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/catalog1_node4')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':catalog1_node4',
                    'attributes' => [
                        'url'                => '/catalog1_node4',
                        'isSlug'             => true,
                        'redirectUrl'        => '/catalog1_node4_new',
                        'redirectStatusCode' => 301,
                        'resourceType'       => 'landing_page',
                        'apiUrl'             => '/api/landingpages/' . $this->getId('landing_page1')
                    ]
                ]
            ],
            $response
        );
    }
}
