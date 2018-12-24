<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Autocomplete\ProductVisibilityLimitedSearchHandler;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class ProductVisibilityLimitedSearchHandlerTest extends FrontendWebTestCase
{
    /**
     * @var Event
     */
    protected $firedEvent;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadFrontendProductData::class
        ]);

        $this->client->getContainer()->set('test_service', $this);
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
     * @param string $searchHandlerName
     * @param array $expectedProducts
     */
    public function testFrontendVisibility(string $searchHandlerName, array $expectedProducts): void
    {
        $url = $this->getUrl(
            'oro_frontend_autocomplete_search',
            [
                'per_page' => 10,
                'query'    => 'pro',
                'name'     => $searchHandlerName
            ]
        );

        $dispatcher = $this->client->getContainer()->get('event_dispatcher');

        /*** @var ProductSearchQueryRestrictionEvent $firedEvent */
        $this->firedEvent = null;

        $dispatcher->addListenerService(
            ProductSearchQueryRestrictionEvent::NAME,
            ['test_service', 'eventCatcher']
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

    /**
     * @return array
     */
    public function frontendVisibilityDataProvider(): array
    {
        return [
            'handler for simple products only' => [
                'searchHandlerName' => 'oro_product_visibility_limited',
                'expectedProductsResult' => [
                    'PRODUCT_7',
                    'PRODUCT_6',
                    'PRODUCT_3',
                    'PRODUCT_2',
                    'PRODUCT_1',
                ]
            ],
            'handler for simple and configurable products' => [
                'searchHandlerName' => 'oro_all_product_visibility_limited',
                'expectedProductsResult' => [
                    'PRODUCT_9',
                    'PRODUCT_8',
                    'PRODUCT_7',
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
     * @param string $searchHandlerName
     * @param array $expectedProducts
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
        $dispatcher = $this->client->getContainer()->get('event_dispatcher');

        /*** @var ProductSearchQueryRestrictionEvent $firedEvent */
        $this->firedEvent = null;

        $dispatcher->addListenerService(
            ProductDBQueryRestrictionEvent::NAME,
            ['test_service', 'eventCatcher']
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

    /**
     * @return array
     */
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
                    'PRODUCT_7',
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
                    'PRODUCT_7',
                    'PRODUCT_8',
                    'PRODUCT_9',
                ]
            ],
        ];
    }

    public function testConvertItemWhenProductWithLocalizedName()
    {
        $this->client->getContainer()->get('request_stack')->push(Request::create(''));

        $this->updateCustomerUserSecurityToken(LoadCustomerUserData::AUTH_USER);
        $this->changeLocalization('en_US');

        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $result = $this->getSearchHandler()->convertItem($product);

        $this->assertEquals(
            [
                'id' => $product->getId(),
                'sku' => $product->getSku(),
                'defaultName.string' => 'product-1.names.en_US',
            ],
            $result
        );
    }

    public function testConvertItemWhenSearchItem()
    {
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
    }

    /**
     * @param Event $event
     */
    public function eventCatcher(Event $event)
    {
        $this->firedEvent = $event;
    }

    /**
     * @param string $localizationCode
     */
    private function changeLocalization(string $localizationCode): void
    {
        $localization = $this->getReference($localizationCode);

        $this->client->getContainer()
            ->get('oro_frontend_localization.manager.user_localization')
            ->setCurrentLocalization($localization);
    }

    /**
     * @return ProductVisibilityLimitedSearchHandler
     */
    private function getSearchHandler(): ProductVisibilityLimitedSearchHandler
    {
        return $this->client->getContainer()
            ->get('oro_form.autocomplete.search_registry')
            ->getSearchHandler('oro_product_visibility_limited');
    }

    /**
     * @param array $productConstants
     * @param array $results
     * @return void
     */
    private function assertResultForProducts(array $productConstants, array $results): void
    {
        self::assertCount(count($productConstants), $results);
        foreach ($productConstants as $productConstant) {
            $reference = \constant(sprintf('%s::%s', LoadProductData::class, $productConstant));

            /** @var Product $product */
            $product = $this->getReference($reference);

            self::assertContains(
                [
                    'id' => $product->getId(),
                    'sku' => $product->getSku(),
                    'defaultName.string' => $product->getDefaultName()
                ],
                $results
            );
        }
    }
}
