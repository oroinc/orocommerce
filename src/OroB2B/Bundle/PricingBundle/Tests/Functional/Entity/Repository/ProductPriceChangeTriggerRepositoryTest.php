<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\ProductPriceChangeTrigger;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\AbstractChangedProductPriceTest;

/**
 * @dbIsolation
 */
class ProductPriceChangeTriggerRepositoryTest extends AbstractChangedProductPriceTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
        ]);
    }

    public function testIsSaved()
    {
        $this->clearTable();
        $productPrice = $this->getProductPrice();
        /** @var PriceList $priceList */
        $priceList = $productPrice->getPriceList();
        $product = $productPrice->getProduct();

        $trigger = new ProductPriceChangeTrigger($priceList, $product);
        //should be false before save
        $this->assertFalse($this->getProductPriceChangeTriggerRepository()->isCreated($trigger));

        $this->getProductPriceManager()->persist($trigger);
        $this->getProductPriceManager()->flush();
        //should be true after save
        $this->assertTrue($this->getProductPriceChangeTriggerRepository()->isCreated($trigger));
    }

    public function testDeleteAll()
    {
        $this->assertNotEmpty($this->getProductPriceChangeTriggerRepository()->findAll());
        $this->getProductPriceChangeTriggerRepository()->deleteAll();
        $this->assertCount(0, $this->getProductPriceChangeTriggerRepository()->findAll());
    }
}
