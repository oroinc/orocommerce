<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCase;

use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
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
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        $this->assertPropertyAccessors(new Product(), $properties);
    }

    public function testCollections()
    {
        $collections = [
            ['names', new LocalizedFallbackValue()],
            ['descriptions', new LocalizedFallbackValue()],
        ];

        $this->assertPropertyCollections(new Product(), $collections);
    }

    public function testToString()
    {
        $product = new Product();

        $this->assertSame('', (string)$product);

        $product->setSku(123);

        $this->assertSame('123', (string)$product);
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

    public function testClone()
    {
        $id = 123;
        $product = new Product();
        $product->getUnitPrecisions()->add(new ProductUnitPrecision());
        $product->getNames()->add(new LocalizedFallbackValue());
        $product->getDescriptions()->add(new LocalizedFallbackValue());

        $refProduct = new \ReflectionObject($product);
        $refId = $refProduct->getProperty('id');
        $refId->setAccessible(true);
        $refId->setValue($product, $id);

        $this->assertEquals($id, $product->getId());
        $this->assertCount(1, $product->getUnitPrecisions());
        $this->assertCount(1, $product->getNames());
        $this->assertCount(1, $product->getDescriptions());

        $productCopy = clone $product;

        $this->assertNull($productCopy->getId());
        $this->assertCount(0, $productCopy->getUnitPrecisions());
        $this->assertCount(0, $productCopy->getNames());
        $this->assertCount(0, $productCopy->getDescriptions());
    }

    public function testGetDefaultName()
    {
        $defaultName = new LocalizedFallbackValue();
        $defaultName->setString('default');

        $localizedName = new LocalizedFallbackValue();
        $localizedName->setString('localized')
            ->setLocale(new Locale());

        $category = new Product();
        $category->addName($defaultName)
            ->addName($localizedName);

        $this->assertEquals($defaultName, $category->getDefaultName());
    }

    /**
     * @param array $names
     * @dataProvider getDefaultNameExceptionDataProvider
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage There must be only one default name
     */
    public function testGetDefaultNameException(array $names)
    {
        $product = new Product();
        foreach ($names as $title) {
            $product->addName($title);
        }
        $product->getDefaultName();
    }

    /**
     * @return array
     */
    public function getDefaultNameExceptionDataProvider()
    {
        return [
            'no default name' => [[]],
            'several default names' => [[new LocalizedFallbackValue(), new LocalizedFallbackValue()]],
        ];
    }

    public function testGetDefaultDescription()
    {
        $defaultDescription = new LocalizedFallbackValue();
        $defaultDescription->setString('default');

        $localizedDescription = new LocalizedFallbackValue();
        $localizedDescription->setString('localized')
            ->setLocale(new Locale());

        $category = new Product();
        $category->addDescription($defaultDescription)
            ->addDescription($localizedDescription);

        $this->assertEquals($defaultDescription, $category->getDefaultDescription());
    }

    /**
     * @param array $descriptions
     * @dataProvider getDefaultDescriptionExceptionDataProvider
     *
     * @expectedException \LogicException
     * @expectedExceptionMessage There must be only one default description
     */
    public function testGetDefaultDescriptionException(array $descriptions)
    {
        $product = new Product();
        foreach ($descriptions as $description) {
            $product->addDescription($description);
        }
        $product->getDefaultDescription();
    }

    /**
     * @return array
     */
    public function getDefaultDescriptionExceptionDataProvider()
    {
        return [
            'no default description' => [[]],
            'several default descriptions' => [[new LocalizedFallbackValue(), new LocalizedFallbackValue()]],
        ];
    }
}
