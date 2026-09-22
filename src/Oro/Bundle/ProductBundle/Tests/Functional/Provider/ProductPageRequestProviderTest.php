<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Provider;

use Oro\Bundle\ProductBundle\Provider\ProductPageRequestProvider;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
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

        // The fixture provides an enabled, slugged product for every type the entity supports, so
        // the actual count depends on which types this edition's ProductTypeProvider considers
        // available (not necessarily all of Product::TYPE_*).
        /** @var ProductTypeProvider $productTypeProvider */
        $productTypeProvider = self::getContainer()->get('oro_product.provider.product_type_provider');
        self::assertCount(count($productTypeProvider->getAvailableProductTypes()), $requests);

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
