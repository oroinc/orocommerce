<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Autocomplete;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityLimitedSearchHandlerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->getContainer()->get('oro_customer.visibility.cache.product.cache_builder')->buildCache();
        $this->getContainer()->get('oro_website_search.indexer')->reindex(Product::class);
    }

    public function testVisibility()
    {
        $url = $this->getUrl('oro_frontend_autocomplete_search', array('per_page'=>10, 'query'=>'product.2', 'name'=>'oro_product_visibility_limited'));
        $this->client->request('GET',$url);
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $data = json_decode($result->getContent(), true);
        $this->assertNotEmpty($data['results']);

    }
}
