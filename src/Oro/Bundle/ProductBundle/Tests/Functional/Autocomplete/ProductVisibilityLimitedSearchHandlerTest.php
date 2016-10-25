<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Autocomplete;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Symfony\Component\HttpFoundation\Request;


use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductVisibilityLimitedData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ProductVisibilityLimitedSearchHandlerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductVisibilityLimitedData::class
        ]);
        $this->getContainer()->get('oro_website_search.indexer')->reindex(Product::class);
        $this->getContainer()->get('oro_customer.visibility.cache.product.cache_builder')->buildCache();
    }

    public function testVisibility()
    {

        $url = $this->getUrl(
            'oro_frontend_autocomplete_search',
            array(
                'per_page'=>10,
                'query'=>'ZZ',
                'name'=>'oro_product_visibility_limited'
            )
        );
        $this->client->request('GET',$url);
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $data = json_decode($result->getContent(), true);
        $this->assertNotEmpty($data);

    }

    public function testBackendVisibility()
    {
        $this->getContainer()->get('request_stack')->push(Request::create('admin'));
        $url = $this->getUrl(
            'oro_frontend_autocomplete_search',
            array(
                'per_page'=>10,
                'query'=>'ZZ',
                'name'=>'oro_product_visibility_limited'
            )
        );
        $this->client->request('GET',$url);
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $data = json_decode($result->getContent(), true);
        $this->assertNotEmpty($data);

    }
}
