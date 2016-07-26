<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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
            LoadProductPrices::class
        ]);
    }

    public function testPostRemove()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BPricingBundle:PriceListToProduct');

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceListToProduct = $em->getRepository('OroB2BPricingBundle:PriceListToProduct')
            ->findOneBy([
                'product' => $product,
                'priceList' => $priceList
            ]);

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
