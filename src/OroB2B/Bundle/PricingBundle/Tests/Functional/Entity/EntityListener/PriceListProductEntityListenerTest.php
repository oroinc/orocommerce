<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;
use OroB2B\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolation
 */
class PriceListProductEntityListenerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListsToProducts',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
        ]);
    }

    public function testPostRemove()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BPricingBundle:PriceListToProduct');

        /** @var PriceListToProduct $priceListToProduct */
        $priceListToProduct = $this->getReference('price_list_1_product_1');
        $priceList = $priceListToProduct->getPriceList();
        $product = $priceListToProduct->getProduct();

        // Check prices for this relation
        $this->assertPricesCount($priceList, $product, 4);

        $em->remove($priceListToProduct);
        $em->flush();

        // Check all prices for this relation deleted
        $this->assertPricesCount($priceList, $product, 0);
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     * @param int $priceCount
     */
    protected function assertPricesCount(PriceList $priceList, Product $product, $priceCount)
    {
        $prices = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BPricingBundle:ProductPrice')
            ->getRepository('OroB2BPricingBundle:ProductPrice')
            ->findBy([
                'priceList' => $priceList,
                'product' => $product
            ]);

        $this->assertCount($priceCount, $prices);
    }
}
