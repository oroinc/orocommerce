<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\Repository\ProductPriceChangeTriggerRepository;
use OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductPriceChangeTriggerRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadPriceLists::class,
            LoadProductData::class
        ]);
    }

    public function testIsExistingTriggerFalse()
    {
        /** @var ProductPriceChangeTriggerRepository $repository */
        $repository = $this
            ->getContainer()
            ->get('doctrine')
            ->getManager()
            ->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $trigger = new ProductPriceChangeTrigger($priceList, $product);

        $this->assertFalse($repository->isExisting($trigger));
    }

    public function testIsExistingTriggerTrue()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var ProductPriceChangeTriggerRepository $repository */
        $repository = $em->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $trigger = new ProductPriceChangeTrigger($priceList, $product);
        $em->persist($trigger);
        $em->flush();

        $this->assertTrue($repository->isExisting($trigger));
    }

    public function testDeleteAll()
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        /** @var ProductPriceChangeTriggerRepository $repository */
        $repository = $em->getRepository('OroB2BPricingBundle:ProductPriceChangeTrigger');
        $this->assertNotEmpty($repository->findBy([]));

        $repository->deleteAll();
        $this->assertCount(0, $repository->findBy([]));
    }
}
