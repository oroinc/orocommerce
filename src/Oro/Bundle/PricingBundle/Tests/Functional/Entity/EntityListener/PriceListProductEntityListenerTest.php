<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

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
        $em = $this->getContainer()->get('doctrine')->getManagerForClass('OroPricingBundle:PriceListToProduct');

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceListToProduct = $em->getRepository('OroPricingBundle:PriceListToProduct')
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
        $prices = $this->getContainer()->get('doctrine')->getManagerForClass('OroPricingBundle:ProductPrice')
            ->getRepository('OroPricingBundle:ProductPrice')
            ->findBy([
                'priceList' => $priceList,
                'product' => $product
            ]);

        $this->assertCount($priceCount, $prices);
    }
}
