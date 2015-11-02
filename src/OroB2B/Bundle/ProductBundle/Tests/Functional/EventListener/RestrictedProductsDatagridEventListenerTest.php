<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts;

/**
 * @dbIsolation
 */
class RestrictedProductsDatagridEventListenerTest extends WebTestCase
{
    /** @var  Product */
    protected $product1;

    /** @var  Product */
    protected $product2;

    /** @var  Product */
    protected $product3;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts']);
        $product1 = $this->getReference(LoadProducts::PRODUCT_1);
        $product2 = $this->getReference(LoadProducts::PRODUCT_2);
        $product3 = $this->getReference(LoadProducts::PRODUCT_3);
    }

    public function testDatagrid()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_datagrid_index', ['gridName' => 'products-select-grid'])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $data = json_decode($result->getContent(), true);
    }
}
