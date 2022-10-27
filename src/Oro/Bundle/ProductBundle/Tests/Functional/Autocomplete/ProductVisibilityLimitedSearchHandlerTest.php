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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @dbIsolationPerTest
 */
class ProductVisibilityLimitedSearchHandlerTest extends FrontendWebTestCase
{
    use ConfigManagerAwareTestTrait;

    /**
     * @var Event
     */
    protected $firedEvent;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadFrontendProductData::class
        ]);
    }

    public function testFrontendVisibilityWithZeroValue()
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

        $this->assertJsonResponseStatusCodeEquals($result, 200);
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

        $dispatcher = $this->client->getContainer()->get('event_dispatcher');

        /*** @var ProductSearchQueryRestrictionEvent $firedEvent */
        $this->firedEvent = null;

        $dispatcher->addListener(
            ProductSearchQueryRestrictionEvent::NAME,
            [$this, 'eventCatcher']
        );

        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $data = json_decode($result->getContent(), true);

        $this->assertResultForProducts($expectedProducts, $data['results']);

        $this->assertNotNull($this->firedEvent, 'Restriction event has not been fired');
        $this->assertInstanceOf(
            ProductSearchQueryRestrictionEvent::class,
            $this->firedEvent
        );

        $dispatcher->removeListener(
            ProductSearchQueryRestrictionEvent::NAME,
            [$this, 'eventCatcher']
        );
    }

    public function frontendVisibilityDataProvider(): array
    {
        return [
            'handler for simple products only' => [
                'query' => 'pro',
                'searchHandlerName' => 'oro_product_visibility_limited',
                'expectedProductsResult' => [
                    'PRODUCT_6',
                    'PRODUCT_3',
                    'PRODUCT_2',
                    'PRODUCT_1',
                ]
            ],
            'handler for simple and configurable products' => [
                'query' => 'pro  ',
                'searchHandlerName' => 'oro_all_product_visibility_limited',
                'expectedProductsResult' => [
                    'PRODUCT_8',
                    'PRODUCT_6',
                    'PRODUCT_3',
                    'PRODUCT_2',
                    'PRODUCT_1',
                ]
            ],
        ];
    }

    /**
     * @dataProvider backendVisibilityDataProvider
     */
    public function testBackendVisibility(string $searchHandlerName, array $expectedProducts): void
    {
        $url = $this->getUrl(
            'oro_form_autocomplete_search',
            [
                'per_page' => 10,
                'query'    => 'pro',
                'name'     => $searchHandlerName
            ]
        );

        $this->client->restart();
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        /*** @var ProductSearchQueryRestrictionEvent $firedEvent */
        $this->firedEvent = null;

        $dispatcher->addListener(
            ProductDBQueryRestrictionEvent::NAME,
            [$this, 'eventCatcher']
        );

        $this->client->request('GET', $url);

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $data = json_decode($result->getContent(), true);

        $this->assertResultForProducts($expectedProducts, $data['results']);

        $this->assertNotNull($this->firedEvent, 'Restriction event has not been fired');
        $this->assertInstanceOf(
            ProductDBQueryRestrictionEvent::class,
            $this->firedEvent
        );

        $dispatcher->removeListener(
            ProductDBQueryRestrictionEvent::NAME,
            [$this, 'eventCatcher']
        );
    }

    public function backendVisibilityDataProvider(): array
    {
        return [
            'handler for simple products only' => [
                'searchHandlerName' => 'oro_product_visibility_limited',
                'expectedProductsResult' => [
                    'PRODUCT_1',
                    'PRODUCT_2',
                    'PRODUCT_3',
                    'PRODUCT_4',
                    'PRODUCT_6',
                ]
            ],
            'handler for simple and configurable products' => [
                'searchHandlerName' => 'oro_all_product_visibility_limited',
                'expectedProductsResult' => [
                    'PRODUCT_1',
                    'PRODUCT_2',
                    'PRODUCT_3',
                    'PRODUCT_4',
                    'PRODUCT_6',
                    'PRODUCT_8',
                ]
            ],
        ];
    }

    public function testConvertItemWhenProductWithLocalizedName()
    {
        $this->client->getContainer()->get('request_stack')->push(Request::create(''));

        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::AUTH_USER);
        $this->changeLocalization('en_CA');

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $result = $this->getSearchHandler()->convertItem($product);

        $this->assertEquals(
            [
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'defaultName.string' => 'product-1.names.en_CA',
            ],
            $result
        );
    }

    public function testConvertItemWhenSearchItem()
    {
        $key = Configuration::getConfigKeyByName(Configuration::ALLOW_PARTIAL_PRODUCT_SEARCH);

        $configManager = self::getConfigManager('global');
        $originalValue = $configManager->get($key);
        $configManager->set($key, true);
        $configManager->flush();

        $this->client->getContainer()->get('request_stack')->push(Request::create(''));

        $searchItems = $this->client->getContainer()->get('oro_product.website_search.repository.product')
            ->getSearchQueryBySkuOrName(LoadProductData::PRODUCT_1, 0, 1)
            ->getResult()
            ->getElements();
        $this->assertCount(1, $searchItems);

        $result = $this->getSearchHandler()->convertItem($searchItems[0]);

        $selectedData = $searchItems[0]->getSelectedData();

        $this->assertArrayHasKey('product_id', $selectedData);
        $this->assertArrayHasKey('sku', $selectedData);
        $this->assertArrayHasKey('name', $selectedData);

        $this->assertEquals(
            [
                'id' => $selectedData['product_id'],
                'sku' => $selectedData['sku'],
                'defaultName.string' => $selectedData['name'],
            ],
            $result
        );

        $configManager->set($key, $originalValue);
        $configManager->flush();
    }

    public function testSearchByMultipleSkus()
    {
        $skuList = [LoadProductData::PRODUCT_2, LoadProductData::PRODUCT_6];
        $this->getContainer()
            ->get('request_stack')
            ->push(Request::create('', Request::METHOD_POST, ['sku' => $skuList]));

        $items = $this->getSearchHandler()->search('', 1, 5);

        $this->assertCount(2, $items['results']);

        $actualSkuList = array_map(function ($item) {
            return $item['sku'];
        }, $items['results']);
        foreach ($actualSkuList as $sku) {
            $this->assertContains($sku, $skuList);
        }
    }

    public function eventCatcher(Event $event)
    {
        $this->firedEvent = $event;
    }

    private function changeLocalization(string $localizationCode): void
    {
        $localization = $this->getReference($localizationCode);

        $this->client->getContainer()
            ->get('oro_frontend_localization.manager.user_localization')
            ->setCurrentLocalization($localization);
    }

    private function getSearchHandler(): ProductVisibilityLimitedSearchHandler
    {
        return $this->client->getContainer()
            ->get('oro_form.autocomplete.search_registry')
            ->getSearchHandler('oro_product_visibility_limited');
    }

    private function assertResultForProducts(array $productConstants, array $results): void
    {
        static::assertCount(\count($productConstants), $results);
        foreach ($productConstants as $productConstant) {
            $reference = \constant(\sprintf('%s::%s', LoadProductData::class, $productConstant));

            /** @var Product $product */
            $product = $this->getReference($reference);
            $found = false;
            foreach ($results as $result) {
                // intentional non-strict comparison
                if ($product->getId() == $result['id']
                    && $product->getSku() == $result['sku']
                    && $product->getDefaultName() == $result['defaultName.string']
                ) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                static::fail(
                    \sprintf(
                        "Result does not contain product '%s' (id: %s, sku: %s, defaultName.string: %s):\n",
                        $reference,
                        $product->getId(),
                        $product->getSku(),
                        $product->getDefaultName()
                    )
                    . \array_reduce($results, function ($output, $item) {
                        return $output . \sprintf(
                            "id: %s, sku: %s, defaultName.string: %s\n",
                            $item['id'],
                            $item['sku'],
                            $item['defaultName.string']
                        );
                    })
                );
                return;
            }
        }
    }
}
