<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductSearchPageRequestProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductPageRequestProviderData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class ProductSearchPageRequestProviderTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        self::getContainer()->get('oro_website_search.indexer')->resetIndex();

        $this->loadFixtures([LoadProductPageRequestProviderData::class]);

        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );
    }

    public function testGetRequestsProducesAWorkingAnonymousSearchResultsPage(): void
    {
        /** @var ProductSearchPageRequestProvider $provider */
        $provider = self::getContainer()->get('oro_product.cache.product_search_page_request_provider');

        $requests = $provider->getRequests();

        self::assertCount(1, $requests);
        /** @var Request $providedRequest */
        $providedRequest = $requests[0];
        self::assertSame('GET', $providedRequest->getMethod());
        self::assertSame('html', $providedRequest->getRequestFormat());

        /** @var Product $product */
        $product = $this->getReference(LoadProductPageRequestProviderData::PRODUCT_SIMPLE);
        self::assertSame($product->getDefaultName()->getString(), $providedRequest->query->get('search'));

        $this->client->request('GET', $providedRequest->getRequestUri());
        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString($product->getSku(), $response->getContent());
    }
}
