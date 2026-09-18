<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Provider;

use Oro\Bundle\CatalogBundle\Provider\CategoryPageRequestProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class CategoryPageRequestProviderTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadCategoryData::class]);
    }

    public function testGetRequestsProducesAWorkingAnonymousCategoryPage(): void
    {
        /** @var CategoryPageRequestProvider $provider */
        $provider = self::getContainer()->get('oro_catalog.cache.category_page_request_provider');

        $requests = $provider->getRequests();

        self::assertCount(1, $requests);
        /** @var Request $providedRequest */
        $providedRequest = $requests[0];
        self::assertSame('GET', $providedRequest->getMethod());
        self::assertSame('html', $providedRequest->getRequestFormat());

        $this->client->request('GET', $providedRequest->getRequestUri());
        $response = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($response, 200);
    }
}
