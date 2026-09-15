<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class EmbeddedFragmentControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private const string SHOPPING_LISTS_BLOCK = 'embedded_shopping_lists';
    private const string SHOPPING_LIST_VIEW_BLOCK = 'embedded_shopping_list_view';
    private const string CONFIG_SCRIPTS_BLOCK = 'layout_js_modules_config';
    private const string TITLE_KEY = 'title';
    private const string PAGE_BLOCK_THAT_ALWAYS_EXISTS = 'page_main_content';
    private const string SHOPPING_LISTS_GRID_NAME = 'frontend-customer-user-shopping-lists-grid';
    private const string EMBEDDED_PATH_HEADER = 'X-Oro-Embedded-Path';
    private const string LOCALIZATION_ID_HEADER = 'HTTP_X-Localization-ID';
    private const string HASH_NAVIGATION_HEADER = 'HTTP_X-Oro-Hash-Navigation';
    private const string PRICE_BY_CUSTOMER_PROXIED_ROUTE = 'oro_pricing_frontend_price_by_customer';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([LoadShoppingLists::class]);
    }

    public function testReturnsBlockMarkupWithConfigScriptsAndEmbeddedMetadata(): void
    {
        $response = $this->requestFragment(self::SHOPPING_LISTS_BLOCK);
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey(self::SHOPPING_LISTS_BLOCK, $content);
        self::assertStringContainsString(self::SHOPPING_LISTS_GRID_NAME, $content[self::SHOPPING_LISTS_BLOCK]);

        self::assertArrayHasKey(self::CONFIG_SCRIPTS_BLOCK, $content);
        self::assertStringContainsString('data-role="config"', $content[self::CONFIG_SCRIPTS_BLOCK]);

        self::assertMatchesRegularExpression('#^https?://[^/]+/build/[\w-]+/$#', $content['buildBase']);
        self::assertStringContainsString(
            json_encode(['publicPath' => parse_url($content['buildBase'], PHP_URL_PATH)]),
            $content[self::CONFIG_SCRIPTS_BLOCK]
        );
        self::assertStorefrontStyleUrls($content['styles']);
        self::assertSame([], $content['proxiedRoutes']);
        self::assertContains('/attachment/', $content['filePaths']);
        self::assertSame([], $content['flashMessages']);

        self::assertSame(
            $this->getUrl('oro_shopping_list_frontend_index'),
            $response->headers->get(self::EMBEDDED_PATH_HEADER)
        );
    }

    public function testLeavesOutWhatTheAlreadyBootedRuntimeReadsOnlyOnceWhenItReloadsTheBlock(): void
    {
        $response = $this->requestFragment(
            self::SHOPPING_LISTS_BLOCK,
            [],
            [self::HASH_NAVIGATION_HEADER => 1]
        );
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey(self::SHOPPING_LISTS_BLOCK, $content);
        self::assertStringContainsString(self::SHOPPING_LISTS_GRID_NAME, $content[self::SHOPPING_LISTS_BLOCK]);
        self::assertArrayNotHasKey(self::CONFIG_SCRIPTS_BLOCK, $content);

        foreach (['buildBase', 'runtimeUrl', 'runtimeIntegrity', 'styles', 'printStyles', 'filePaths'] as $key) {
            self::assertArrayNotHasKey($key, $content);
        }
        self::assertSame([], $content['flashMessages']);
    }

    public function testTheRuntimeUrlCarriesTheAssetVersionSoARebuiltRuntimeIsNotServedFromTheBrowserCache(): void
    {
        $content = self::getJsonResponseContent(
            $this->requestFragment(self::SHOPPING_LISTS_BLOCK),
            Response::HTTP_OK
        );

        self::assertSame(
            $content['buildBase'] . 'embedded_app.js' . self::getAssetVersionQuery(),
            $content['runtimeUrl']
        );
    }

    public function testStyleUrlsCarryTheAssetVersionSoAnAssetBuildIsNotServedFromACachedStylesheet(): void
    {
        $content = self::getJsonResponseContent(
            $this->requestFragment(self::SHOPPING_LISTS_BLOCK),
            Response::HTTP_OK
        );

        $styleUrls = array_merge($content['styles'], $content['printStyles']);
        self::assertNotEmpty($styleUrls);

        foreach ($styleUrls as $styleUrl) {
            self::assertSame(self::getAssetVersionQuery(), (string) strstr($styleUrl, '?'));
        }
    }

    public function testReturnsRequestedEntityAndProxiedRoutesForBlockWithRouteParameter(): void
    {
        $shoppingList = $this->getShoppingList(LoadShoppingLists::SHOPPING_LIST_1);

        $response = $this->requestFragment(self::SHOPPING_LIST_VIEW_BLOCK, ['id' => $shoppingList->getId()]);
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey(self::SHOPPING_LIST_VIEW_BLOCK, $content);
        self::assertStringContainsString($shoppingList->getLabel(), $content[self::SHOPPING_LIST_VIEW_BLOCK]);
        self::assertSame([self::PRICE_BY_CUSTOMER_PROXIED_ROUTE], $content['proxiedRoutes']);

        self::assertSame(
            $this->getUrl('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()]),
            $response->headers->get(self::EMBEDDED_PATH_HEADER)
        );
    }

    public function testCarriesThePageTitleTheTargetPageItselfRenders(): void
    {
        $shoppingList = $this->getShoppingList(LoadShoppingLists::SHOPPING_LIST_1);
        $routeParameters = ['id' => $shoppingList->getId()];

        $content = self::getJsonResponseContent(
            $this->requestFragment(self::SHOPPING_LIST_VIEW_BLOCK, $routeParameters),
            Response::HTTP_OK
        );

        $page = $this->client->request(
            'GET',
            $this->getUrl('oro_shopping_list_frontend_view', $routeParameters)
        );
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);

        self::assertStringContainsString($shoppingList->getLabel(), $content[self::TITLE_KEY]);
        self::assertSame($page->filter('title')->text(), $content[self::TITLE_KEY]);
    }

    public function testFallsBackToTargetRouteDefaultWhenOptionalRouteParameterIsOmitted(): void
    {
        $shoppingList = $this->getShoppingList(LoadShoppingLists::SHOPPING_LIST_1);
        $this->getCurrentShoppingListManager()->setCurrent($this->getAuthenticatedCustomerUser(), $shoppingList);

        $response = $this->requestFragment(self::SHOPPING_LIST_VIEW_BLOCK);
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey(self::SHOPPING_LIST_VIEW_BLOCK, $content);
        self::assertStringContainsString($shoppingList->getLabel(), $content[self::SHOPPING_LIST_VIEW_BLOCK]);

        self::assertSame(
            $this->getUrl('oro_shopping_list_frontend_view'),
            $response->headers->get(self::EMBEDDED_PATH_HEADER)
        );
    }

    public function testKeepsDatagridStateThatArrivesAsAnArrayParameter(): void
    {
        $response = $this->requestFragment(
            self::SHOPPING_LISTS_BLOCK,
            ['grid' => [self::SHOPPING_LISTS_GRID_NAME => 'i=1&p=10']]
        );

        $content = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertStringContainsString(self::SHOPPING_LISTS_GRID_NAME, $content[self::SHOPPING_LISTS_BLOCK]);
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

    public function testEmbeddedBlockIsBuiltOnlyForTheFragmentRequestAndNotForTheTargetPageItself(): void
    {
        $this->client->request('GET', $this->getUrl('oro_shopping_list_frontend_index'));

        $pageResponse = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($pageResponse, Response::HTTP_OK);
        self::assertStringContainsString(self::SHOPPING_LISTS_GRID_NAME, $pageResponse->getContent());

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_shopping_list_frontend_index',
                ['layout_block_ids' => [self::SHOPPING_LISTS_BLOCK, self::PAGE_BLOCK_THAT_ALWAYS_EXISTS]]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $pageBlocks = self::getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);

        self::assertArrayHasKey(self::PAGE_BLOCK_THAT_ALWAYS_EXISTS, $pageBlocks);
        self::assertStringContainsString(
            self::SHOPPING_LISTS_GRID_NAME,
            $pageBlocks[self::PAGE_BLOCK_THAT_ALWAYS_EXISTS]
        );
        self::assertArrayNotHasKey(
            self::SHOPPING_LISTS_BLOCK,
            $pageBlocks,
            'The embedded block must not exist in the layout of an ordinary storefront page request.'
        );

        $fragmentBlocks = self::getJsonResponseContent(
            $this->requestFragment(self::SHOPPING_LISTS_BLOCK),
            Response::HTTP_OK
        );

        self::assertArrayHasKey(self::SHOPPING_LISTS_BLOCK, $fragmentBlocks);
        self::assertStringContainsString(
            self::SHOPPING_LISTS_GRID_NAME,
            $fragmentBlocks[self::SHOPPING_LISTS_BLOCK]
        );
    }

    /**
     * @dataProvider ignoredLocalizationHeaderDataProvider
     */
    public function testRendersFragmentWhenLocalizationHeaderCannotBeApplied(string $localizationHeader): void
    {
        $content = $this->requestFragmentWithLocalizationHeader($localizationHeader);

        self::assertArrayHasKey(self::SHOPPING_LISTS_BLOCK, $content);
        self::assertStringContainsString(self::SHOPPING_LISTS_GRID_NAME, $content[self::SHOPPING_LISTS_BLOCK]);
    }

    public function ignoredLocalizationHeaderDataProvider(): array
    {
        return [
            'not an integer' => ['not-a-number'],
            'id of a localization that does not exist' => ['999999']
        ];
    }

    public function testRendersFragmentInRequestedEnabledLocalization(): void
    {
        $enabledLocalizationIds = $this->getEnabledLocalizationIds();

        $content = $this->requestFragmentWithLocalizationHeader((string)reset($enabledLocalizationIds));

        self::assertArrayHasKey(self::SHOPPING_LISTS_BLOCK, $content);
        self::assertStringContainsString(self::SHOPPING_LISTS_GRID_NAME, $content[self::SHOPPING_LISTS_BLOCK]);
    }

    private function requestFragmentWithLocalizationHeader(string $localizationHeader): array
    {
        return self::getJsonResponseContent(
            $this->requestFragment(
                self::SHOPPING_LISTS_BLOCK,
                [],
                [self::LOCALIZATION_ID_HEADER => $localizationHeader]
            ),
            Response::HTTP_OK
        );
    }

    private function requestFragment(string $block, array $routeParameters = [], array $server = []): Response
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_storefront_embedded_fragment', ['block' => $block] + $routeParameters),
            [],
            [],
            $server
        );

        return $this->client->getResponse();
    }

    private static function assertStorefrontStyleUrls(array $styleUrls): void
    {
        self::assertNotEmpty($styleUrls);
        $versionQuery = preg_quote(self::getAssetVersionQuery(), '#');
        foreach ($styleUrls as $styleUrl) {
            self::assertMatchesRegularExpression('#^/build/[\w-]+/.+\.css' . $versionQuery . '$#', $styleUrl);
        }
        self::assertStringContainsString('critical', reset($styleUrls));
    }

    private static function getAssetVersionQuery(): string
    {
        $version = self::getContainer()->get('assets.packages')->getVersion('/build/');

        return $version === '' ? '' : '?v=' . $version;
    }

    private function getEnabledLocalizationIds(): array
    {
        $enabledLocalizationIds = array_map(
            'intval',
            (array)self::getConfigManager()->get('oro_locale.enabled_localizations')
        );
        self::assertNotEmpty(
            $enabledLocalizationIds,
            'The test environment must have at least one enabled localization.'
        );

        return $enabledLocalizationIds;
    }

    private function getShoppingList(string $reference): ShoppingList
    {
        return $this->getReference($reference);
    }

    private function getAuthenticatedCustomerUser(): CustomerUser
    {
        return self::getContainer()->get('doctrine')->getRepository(CustomerUser::class)
            ->findOneBy(['email' => LoadCustomerUserData::AUTH_USER]);
    }

    private function getCurrentShoppingListManager(): CurrentShoppingListManager
    {
        return self::getContainer()->get('oro_shopping_list.manager.current_shopping_list');
    }
}
