<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserRoleACLData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedFragmentController;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class EmbeddedFragmentControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restoreConfigValues();
    }

    public function testReturnsBlockMarkupWithConfigScriptsAndEmbeddedMetadata(): void
    {
        $response = $this->requestFragment('embedded_shopping_lists');
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('embedded_shopping_lists', $content);
        self::assertStringContainsString(
            'frontend-customer-user-shopping-lists-grid',
            $content['embedded_shopping_lists']
        );

        self::assertArrayHasKey('layout_js_modules_config', $content);
        self::assertStringContainsString('data-role="config"', $content['layout_js_modules_config']);

        $runtimePath = (string) parse_url($content['runtimeUrl'], PHP_URL_PATH);
        self::assertMatchesRegularExpression('#^/build/[\w-]+/embedded_app\.js$#', $runtimePath);
        self::assertStringContainsString(
            json_encode(['publicPath' => dirname($runtimePath) . '/']),
            $content['layout_js_modules_config']
        );
        self::assertNotEmpty($content['styles']);
        self::assertSame(
            [],
            preg_grep('#^/build/[\w-]+/.+\.css(\?.*)?$#', $content['styles'], PREG_GREP_INVERT)
        );
        self::assertStringContainsString('critical', $content['styles'][0]);
        self::assertSame([], $content['flashMessages']);

        self::assertSame($this->getUrl('oro_shopping_list_frontend_index'), $content['subtreeUrl']);
        self::assertFalse($response->headers->has('X-Oro-Embedded-Path'));
    }

    public function testLeavesOutWhatTheAlreadyBootedRuntimeReadsOnlyOnceWhenItReloadsTheBlock(): void
    {
        $response = $this->requestFragment(
            'embedded_shopping_lists',
            [],
            ['HTTP_' . ResponseHashnavListener::HASH_NAVIGATION_HEADER => 1]
        );
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('embedded_shopping_lists', $content);
        self::assertStringContainsString(
            'frontend-customer-user-shopping-lists-grid',
            $content['embedded_shopping_lists']
        );
        self::assertSame(
            [],
            array_intersect_key(
                $content,
                array_flip([
                    'layout_js_modules_config',
                    'runtimeUrl',
                    'runtimeIntegrity',
                    'styles',
                    'printStyles'
                ])
            )
        );
        self::assertSame([], $content['flashMessages']);
    }

    public function testTheRuntimeUrlCarriesTheAssetVersionSoARebuiltRuntimeIsNotServedFromTheBrowserCache(): void
    {
        $content = self::getJsonResponseContent(
            $this->requestFragment('embedded_shopping_lists'),
            Response::HTTP_OK
        );

        $runtimePath = (string) parse_url($content['runtimeUrl'], PHP_URL_PATH);
        self::assertMatchesRegularExpression('#^/build/[\w-]+/embedded_app\.js$#', $runtimePath);
        self::assertSame($runtimePath . self::getAssetVersionQuery(), $content['runtimeUrl']);
    }

    public function testStyleUrlsCarryTheAssetVersionSoAnAssetBuildIsNotServedFromACachedStylesheet(): void
    {
        $content = self::getJsonResponseContent(
            $this->requestFragment('embedded_shopping_lists'),
            Response::HTTP_OK
        );

        $styleUrls = array_merge($content['styles'], $content['printStyles']);
        self::assertNotEmpty($styleUrls);

        $versionQuery = self::getAssetVersionQuery();
        self::assertSame(
            [],
            array_filter(
                $styleUrls,
                static fn (string $styleUrl): bool => (string) strstr($styleUrl, '?') !== $versionQuery
            )
        );
    }

    public function testReturnsRequestedEntityForBlockWithRouteParameter(): void
    {
        $this->loadFixtures([LoadShoppingLists::class]);
        $shoppingList = $this->getShoppingList(LoadShoppingLists::SHOPPING_LIST_1);

        $response = $this->requestFragment('embedded_shopping_list_view', ['id' => $shoppingList->getId()]);
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('embedded_shopping_list_view', $content);
        self::assertStringContainsString($shoppingList->getLabel(), $content['embedded_shopping_list_view']);

        self::assertSame(
            $this->getUrl('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()]),
            $content['subtreeUrl']
        );
    }

    public function testCarriesThePageTitleTheTargetPageItselfRenders(): void
    {
        $this->loadFixtures([LoadShoppingLists::class]);
        $shoppingList = $this->getShoppingList(LoadShoppingLists::SHOPPING_LIST_1);
        $routeParameters = ['id' => $shoppingList->getId()];

        $content = self::getJsonResponseContent(
            $this->requestFragment('embedded_shopping_list_view', $routeParameters),
            Response::HTTP_OK
        );

        $page = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view', $routeParameters)
        );
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);

        self::assertStringContainsString($shoppingList->getLabel(), $content['title']);
        self::assertSame($page->filter('title')->text(), $content['title']);
    }

    public function testFallsBackToTargetRouteDefaultWhenOptionalRouteParameterIsOmitted(): void
    {
        $this->loadFixtures([LoadShoppingLists::class]);
        $shoppingList = $this->getShoppingList(LoadShoppingLists::SHOPPING_LIST_1);
        self::getContainer()->get('oro_shopping_list.manager.current_shopping_list')->setCurrent(
            self::getContainer()->get('doctrine')->getRepository(CustomerUser::class)
                ->findOneBy(['email' => LoadCustomerUserData::AUTH_USER]),
            $shoppingList
        );

        $response = $this->requestFragment('embedded_shopping_list_view');
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('embedded_shopping_list_view', $content);
        self::assertStringContainsString($shoppingList->getLabel(), $content['embedded_shopping_list_view']);

        self::assertSame($this->getUrl('oro_shopping_list_frontend_view'), $content['subtreeUrl']);
    }

    public function testRendersTheBlockWhenARouteParameterArrivesAsAnArray(): void
    {
        $response = $this->requestFragment(
            'embedded_shopping_lists',
            ['grid' => ['frontend-customer-user-shopping-lists-grid' => 'i=1&p=10']]
        );

        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertStringContainsString(
            'frontend-customer-user-shopping-lists-grid',
            $content['embedded_shopping_lists']
        );
    }

    public function testReturnsNotFoundForBlockThatIsNotRegistered(): void
    {
        $response = $this->requestFragment('embedded_unknown');

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testReturnsNotFoundWhenBlockDoesNotMatchTheRouteRequirement(): void
    {
        $this->client->request('GET', '/embedded/fragment/not_embedded');

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testEmbeddedBlockIsNotBuiltForTheTargetPageItself(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_shopping_list_frontend_index',
                ['layout_block_ids' => ['embedded_shopping_lists', 'page_main_content']]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $pageBlocks = self::getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);

        self::assertArrayHasKey('page_main_content', $pageBlocks);
        self::assertStringContainsString(
            'frontend-customer-user-shopping-lists-grid',
            $pageBlocks['page_main_content']
        );
        self::assertArrayNotHasKey(
            'embedded_shopping_lists',
            $pageBlocks,
            'The embedded block must not exist in the layout of an ordinary storefront page request.'
        );
    }

    public function testDeniesTheSameBlocksItsTargetPageDenies(): void
    {
        $this->loadFixtures([LoadCustomerUserRoleACLData::class]);
        $this->loginUser(LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY);

        $this->client->request('GET', $this->getUrl('oro_customer_frontend_customer_user_role_create'));
        $pageResponse = $this->client->getResponse();

        $fragmentResponse = $this->requestFragment('embedded_customer_user_role_create');

        self::assertSame(
            $pageResponse->getStatusCode(),
            $fragmentResponse->getStatusCode(),
            'A fragment must not widen access to the page behind it.'
        );
        self::assertResponseStatusCodeEquals($fragmentResponse, Response::HTTP_FORBIDDEN);
    }

    public function testDeniesAFormSubmitToABlockWhoseTargetPageIsGuardedOnlyByAnAclAttribute(): void
    {
        $this->loadFixtures([LoadCustomerUserRoleACLData::class]);
        $this->loginUser(LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP_VIEW_ONLY);

        $this->client->request(
            'POST',
            $this->getUrl(
                EmbeddedFragmentController::ROUTE_NAME,
                ['block' => 'embedded_customer_user_role_create']
            ),
            ['oro_customer_frontend_customer_user_role' => ['label' => 'Role Submitted Through A Fragment']]
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
        self::assertNull(
            self::getContainer()->get('doctrine')->getRepository(CustomerUserRole::class)
                ->findOneBy(['label' => 'Role Submitted Through A Fragment'])
        );
    }

    public function testServesBlockWhoseTargetPageTheRoleAllows(): void
    {
        $this->loadFixtures([LoadCustomerUserRoleACLData::class]);
        $this->loginUser(LoadCustomerUserRoleACLData::USER_ACCOUNT_1_ROLE_DEEP);

        /** @var CustomerUserRole $role */
        $role = $this->getReference(LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL);

        $response = $this->requestFragment('embedded_customer_user_role_view', ['id' => $role->getId()]);
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('embedded_customer_user_role_view', $content);
    }

    public function testDeniesBlockOfAnEntityThatBelongsToAnotherCustomer(): void
    {
        $this->loadFixtures([LoadCustomerUserRoleACLData::class]);
        $this->loginUser(LoadCustomerUserRoleACLData::USER_ACCOUNT_2_ROLE_LOCAL);

        /** @var CustomerUserRole $role */
        $role = $this->getReference(LoadCustomerUserRoleACLData::ROLE_WITH_ACCOUNT_1_2_USER_LOCAL);

        $response = $this->requestFragment('embedded_customer_user_role_view', ['id' => $role->getId()]);

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testServesBlockWhenTheTargetPageFeatureIsEnabled(): void
    {
        $response = $this->requestFragment('embedded_rfp_requests');
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('embedded_rfp_requests', $content);
    }

    public function testReturnsNotFoundLikeTheTargetPageWhenItsFeatureIsDisabled(): void
    {
        $this->setConfigValue('oro_rfp.frontend_feature_enabled', false);

        $this->client->request('GET', $this->getUrl('oro_rfp_frontend_request_index'));
        $pageResponse = $this->client->getResponse();

        $fragmentResponse = $this->requestFragment('embedded_rfp_requests');

        self::assertResponseStatusCodeEquals($fragmentResponse, Response::HTTP_NOT_FOUND);
        self::assertSame(
            $pageResponse->getStatusCode(),
            $fragmentResponse->getStatusCode(),
            'A fragment must not open a page that its feature toggle hides.'
        );
    }

    private function requestFragment(string $block, array $routeParameters = [], array $server = []): Response
    {
        $this->client->request(
            'GET',
            $this->getUrl(EmbeddedFragmentController::ROUTE_NAME, ['block' => $block] + $routeParameters),
            [],
            [],
            $server
        );

        return $this->client->getResponse();
    }

    private static function getAssetVersionQuery(): string
    {
        $version = self::getContainer()->get('assets.packages')->getVersion('/build/');

        return $version === '' ? '' : '?v=' . $version;
    }

    private function getShoppingList(string $reference): ShoppingList
    {
        return $this->getReference($reference);
    }
}
