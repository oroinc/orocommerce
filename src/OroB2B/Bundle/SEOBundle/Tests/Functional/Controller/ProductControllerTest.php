<?php

namespace OroB2B\Bundle\SEOBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    use SEOViewSectionTrait;
    
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData']);
    }

    public function testViewProduct()
    {
        $product = $this->getProduct();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $product->getId()]));

        $this->checkSeoSectionExistence($crawler);
    }


    public function testEditProduct()
    {
        $product = $this->getProduct();

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $product->getId()]));

        $this->checkSeoSectionExistence($crawler);
    }

    protected function getProduct()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository(
            $this->getContainer()->getParameter('orob2b_product.entity.product.class')
        );

        return $repository->findOneBy(['sku' => 'product.1']);
    }
}
