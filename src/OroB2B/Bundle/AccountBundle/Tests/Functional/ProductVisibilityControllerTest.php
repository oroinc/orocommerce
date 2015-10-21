<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts;

/**
 * @dbIsolation
 */
class ProductVisibilityControllerTest extends WebTestCase
{
    /** @var  Product */
    protected $product;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(
            [
                'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts',
                'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts',
            ]
        );

        $this->product = $this->getReference(LoadProducts::PRODUCT_1);
    }

    public function testView()
    {
        $websites = $this->client->getContainer()->get('doctrine')->getManagerForClass(
            'OroB2BWebsiteBundle:Website'
        )->getRepository('OroB2BWebsiteBundle:Website')->findAll();
    }
}