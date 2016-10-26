<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Search;

use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceLists;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolation
 */
class ProductRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([
            LoadProductData::class,
            LoadCombinedPriceLists::class,
            LoadProductVisibilityData::class,
        ]);

        $this->getContainer()->get('oro_account.visibility.cache.product.cache_builder')->buildCache();
        $this->getContainer()->get('oro_website_search.indexer')->reindex(Product::class);
    }

    public function testFindOne()
    {
        $exampleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        /**
         * @var $product \Oro\Bundle\SearchBundle\Query\Result\Item
         */
        $product = $this->client->getContainer()->get('oro_product.search.repository.product')->findOne(
            $exampleProduct->getId()
        );

        $this->assertNotNull($product);
        $this->assertEquals($product->getSelectedData()['product_id'], $exampleProduct->getId());
    }
}
