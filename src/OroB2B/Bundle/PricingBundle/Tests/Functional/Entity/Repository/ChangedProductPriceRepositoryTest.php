<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\Repository;

use OroB2B\Bundle\PricingBundle\Entity\ChangedProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener\AbstractChangedProductPriceTest;

/**
 * @dbIsolation
 */
class ChangedProductPriceRepositoryTest extends AbstractChangedProductPriceTest
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

        $changedProductPrice = new ChangedProductPrice($priceList, $product);
        //should be false before save
        $this->assertFalse($this->getChangedProductPriceRepository()->isCreated($changedProductPrice));

        $this->getProductPriceManager()->persist($changedProductPrice);
        $this->getProductPriceManager()->flush();
        //should be true after save
        $this->assertTrue($this->getChangedProductPriceRepository()->isCreated($changedProductPrice));
    }
}
