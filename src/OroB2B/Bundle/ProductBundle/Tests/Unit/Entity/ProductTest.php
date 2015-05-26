<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductTest extends EntityTestCase
{

    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['sku', 'sku-test-01'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['category', new Category()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        $this->assertPropertyAccessors(new Product(), $properties);
    }

    public function testPrePersist()
    {
        $product = new Product();
        $product->prePersist();
        $this->assertInstanceOf('\DateTime', $product->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $product->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $product = new Product();
        $product->preUpdate();
        $this->assertInstanceOf('\DateTime', $product->getUpdatedAt());
    }

    public function testUnitRelation()
    {
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit((new ProductUnit())->setCode('kg'));
        $unitPrecision->setPrecision(0);

        $product = new Product();

        $this->assertCount(0, $product->getUnitPrecisions());

        // Add new ProductUnitPrecision
        $this->assertSame($product, $product->addUnitPrecision($unitPrecision));
        $this->assertCount(1, $product->getUnitPrecisions());

        $actual = $product->getUnitPrecisions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$unitPrecision], $actual->toArray());

        // Add already added ProductUnitPrecision
        $this->assertSame($product, $product->addUnitPrecision($unitPrecision));
        $this->assertCount(1, $product->getUnitPrecisions());

        // Remove ProductUnitPrecision
        $this->assertSame($product, $product->removeUnitPrecision($unitPrecision));
        $this->assertCount(0, $product->getUnitPrecisions());

        $actual = $product->getUnitPrecisions();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertNotContains($unitPrecision, $actual->toArray());
    }

    public function testGetUnitPrecisionByUnitCode()
    {
        $unit = new ProductUnit();
        $unit
            ->setCode('kg')
            ->setDefaultPrecision(3);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setUnit($unit)
            ->setPrecision($unit->getDefaultPrecision());

        $product = new Product();
        $product->addUnitPrecision($unitPrecision);

        $this->assertNull($product->getUnitPrecision('item'));
        $this->assertEquals($unitPrecision, $product->getUnitPrecision('kg'));
    }

    public function testGetAvailableUnitCodes()
    {
        $unit = new ProductUnit();
        $unit
            ->setCode('kg')
            ->setDefaultPrecision(3);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setUnit($unit)
            ->setPrecision($unit->getDefaultPrecision());

        $product = new Product();
        $product->addUnitPrecision($unitPrecision);

        $this->assertEquals(['kg'], $product->getAvailableUnitCodes());
    }

    public function testPriceRelation()
    {
        $price = new ProductPrice();
        $price->setQuantity(12);

        $product = new Product();
        $product->setSku('test');

        $this->assertCount(0, $product->getPrices());

        // Add new ProductUnitPrecision
        $this->assertSame($product, $product->addPrice($price));
        $this->assertCount(1, $product->getPrices());
        $this->assertEquals($product, $price->getProduct());
        $this->assertEquals($product->getSku(), $price->getProductSku());

        $actual = $product->getPrices();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$price], $actual->toArray());

        // Add already added ProductUnitPrecision
        $this->assertSame($product, $product->addPrice($price));
        $this->assertCount(1, $product->getPrices());

        // Remove ProductUnitPrecision
        $this->assertSame($product, $product->removePrice($price));
        $this->assertCount(0, $product->getPrices());

        $actual = $product->getPrices();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertNotContains($price, $actual->toArray());
    }
}
