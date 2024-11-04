<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedSearchHandler;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @dbIsolationPerTest
 */
class ProductVisibilityLimitedSearchHandlerTest extends FrontendWebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?Event $firedEvent = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductKitData::class,
            LoadFrontendProductData::class
        ]);
    }

    public function testFrontendVisibilityWithZeroValue(): void
    {
        $url = $this->getUrl(
            'oro_frontend_autocomplete_search',
            [
                'per_page' => 10,
                'query' => '0',
                'name' => 'oro_product_visibility_limited'
            ]
        );

        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
    }

    /**
     * @dataProvider frontendVisibilityDataProvider
     */
    public function testFrontendVisibility(string $query, string $searchHandlerName, array $expectedProducts): void
    {
        $url = $this->getUrl(
            'oro_frontend_autocomplete_search',
            [
                'per_page' => 10,
                'query'    => $query,
                'name'     => $searchHandlerName
            ]
        );

        $this->firedEvent = null;
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $dispatcher->addListener(ProductSearchQueryRestrictionEvent::NAME, [$this, 'eventCatcher']);

        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 200);
        $data = self::jsonToArray($result->getContent());

        $this->assertResultForProducts($expectedProducts, $data['results']);

        self::assertNotNull($this->firedEvent, 'Restriction event has not been fired');
        self::assertInstanceOf(ProductSearchQueryRestrictionEvent::class, $this->firedEvent);

        $dispatcher->removeListener(ProductSearchQueryRestrictionEvent::NAME, [$this, 'eventCatcher']);
    }

    public function frontendVisibilityDataProvider(): array
    {
        return [
            'handler for simple and kits products' => [
                'query' => 'pro',
                'searchHandlerName' => 'oro_product_visibility_limited',
                'expectedProductsResult' => [
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_1,
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductKitData::PRODUCT_KIT_2,
                    LoadProductKitData::PRODUCT_KIT_3,
                ]
            ],
            'handler for simple, configurable and kit products' => [
                'query' => 'pro  ',
                'searchHandlerName' => 'oro_all_product_visibility_limited',
                'expectedProductsResult' => [
                    LoadProductData::PRODUCT_8,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_1,
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductKitData::PRODUCT_KIT_2,
                    LoadProductKitData::PRODUCT_KIT_3,
                ]
            ],
        ];
    }

    /**
     * @dataProvider backendVisibilityDataProvider
     */
    public function testBackendVisibility(string $searchHandlerName, array $expectedProducts): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $url = $this->getUrl(
            'oro_form_autocomplete_search',
            [
                'per_page' => 10,
                'query'    => 'pro',
                'name'     => $searchHandlerName
            ]
        );

        $this->client->restart();

        $this->firedEvent = null;
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $dispatcher->addListener(ProductDBQueryRestrictionEvent::NAME, [$this, 'eventCatcher']);

        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 200);
        $data = self::jsonToArray($result->getContent());

        $this->assertResultForProducts($expectedProducts, $data['results']);

        self::assertNotNull($this->firedEvent, 'Restriction event has not been fired');
        self::assertInstanceOf(ProductDBQueryRestrictionEvent::class, $this->firedEvent);

        $dispatcher->removeListener(ProductDBQueryRestrictionEvent::NAME, [$this, 'eventCatcher']);
    }

    public function backendVisibilityDataProvider(): array
    {
        return [
            'handler for simple and kits products' => [
                'searchHandlerName' => 'oro_product_visibility_limited',
                'expectedProductsResult' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_6,
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductKitData::PRODUCT_KIT_2,
                    LoadProductKitData::PRODUCT_KIT_3,
                ]
            ],
            'handler for simple, configurable and kit products' => [
                'searchHandlerName' => 'oro_all_product_visibility_limited',
                'expectedProductsResult' => [
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_6,
                    LoadProductData::PRODUCT_8,
                    LoadProductKitData::PRODUCT_KIT_1,
                    LoadProductKitData::PRODUCT_KIT_2,
                    LoadProductKitData::PRODUCT_KIT_3,
                ]
            ],
        ];
    }

    public function testConvertItemWhenProductWithLocalizedName(): void
    {
        self::getContainer()->get('request_stack')->push(Request::create(''));

        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::AUTH_USER);
        $this->changeLocalization('en_CA');

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $result = $this->getSearchHandler()->convertItem($product);

        self::assertEquals(
            [
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'defaultName.string' => 'product-1.names.en_CA',
                'type' => $product->getType(),
            ],
            $result
        );
    }

    public function testConvertItemWhenSearchItem(): void
    {
        $key = Configuration::getConfigKeyByName(Configuration::ALLOW_PARTIAL_PRODUCT_SEARCH);

        $configManager = self::getConfigManager();
        $originalValue = $configManager->get($key);
        $configManager->set($key, true);
        $configManager->flush();

        self::getContainer()->get('request_stack')->push(Request::create(''));

        $searchItems = self::getContainer()->get('oro_product.website_search.repository.product')
            ->getSearchQueryBySkuOrName(LoadProductData::PRODUCT_1, 0, 1)
            ->getResult()
            ->getElements();
        self::assertCount(1, $searchItems);

        $result = $this->getSearchHandler()->convertItem($searchItems[0]);

        $selectedData = $searchItems[0]->getSelectedData();

        self::assertArrayHasKey('product_id', $selectedData);
        self::assertArrayHasKey('sku', $selectedData);
        self::assertArrayHasKey('name', $selectedData);
        self::assertArrayHasKey('type', $selectedData);

        self::assertEquals(
            [
                'id' => $selectedData['product_id'],
                'sku' => $selectedData['sku'],
                'defaultName.string' => $selectedData['name'],
                'type' => $selectedData['type'],
            ],
            $result
        );

        $configManager->set($key, $originalValue);
        $configManager->flush();
    }

    public function testSearchByMultipleSkus(): void
    {
        $skuList = [LoadProductData::PRODUCT_2, LoadProductData::PRODUCT_6];
        self::getContainer()
            ->get('request_stack')
            ->push(Request::create('', Request::METHOD_POST, ['sku' => $skuList]));

        $items = $this->getSearchHandler()->search('', 1, 5);

        self::assertCount(2, $items['results']);

        $actualSkuList = array_map(function ($item) {
            return $item['sku'];
        }, $items['results']);
        foreach ($actualSkuList as $sku) {
            self::assertContains($sku, $skuList);
        }
    }

    public function eventCatcher(Event $event): void
    {
        $this->firedEvent = $event;
    }

    private function changeLocalization(string $localizationCode): void
    {
        $localization = $this->getReference($localizationCode);

        self::getContainer()->get('oro_frontend_localization.manager.user_localization')
            ->setCurrentLocalization($localization);
    }

    private function getSearchHandler(): ProductVisibilityLimitedSearchHandler
    {
        return self::getContainer()->get('oro_form.autocomplete.search_registry')
            ->getSearchHandler('oro_product_visibility_limited');
    }

    private function assertResultForProducts(array $productConstants, array $results): void
    {
        static::assertCount(\count($productConstants), $results);
        foreach ($productConstants as $productConstant) {
            /** @var Product $product */
            $product = $this->getReference($productConstant);
            $found = false;
            foreach ($results as $result) {
                // intentional non-strict comparison
                if ($product->getId() === (int)$result['id']
                    && $product->getSku() === $result['sku']
                    && (string)$product->getDefaultName() === $result['defaultName.string']
                ) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                self::fail(
                    sprintf(
                        "Result does not contain product '%s' (id: %s, sku: %s, defaultName.string: %s):\n",
                        $productConstant,
                        $product->getId(),
                        $product->getSku(),
                        (string)$product->getDefaultName()
                    )
                    . array_reduce($results, function ($output, $item) {
                        return $output . sprintf(
                            "id: %s, sku: %s, defaultName.string: %s\n",
                            $item['id'],
                            $item['sku'],
                            $item['defaultName.string']
                        );
                    })
                );
            }
        }
        self::assertCount(\count($productConstants), $results);
    }
}
