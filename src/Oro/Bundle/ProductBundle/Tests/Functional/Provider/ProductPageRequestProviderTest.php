<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\ProductBundle\Provider\ProductPageRequestProvider;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductPageRequestProviderData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class ProductPageRequestProviderTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductPageRequestProviderData::class]);
    }

    public function testGetRequestsProducesAWorkingAnonymousProductPagePerType(): void
    {
        /** @var ProductPageRequestProvider $provider */
        $provider = self::getContainer()->get('oro_product.cache.product_page_request_provider');

        $requests = $provider->getRequests();

        // ensures an enabled, slugged product of each type is present.
        self::assertCount(3, $requests);

        foreach ($requests as $providedRequest) {
            /** @var Request $providedRequest */
            self::assertSame('GET', $providedRequest->getMethod());
            self::assertSame('html', $providedRequest->getRequestFormat());

            $this->client->request('GET', $providedRequest->getRequestUri());
            $response = $this->client->getResponse();

            self::assertHtmlResponseStatusCodeEquals($response, 200);
        }
    }
}
