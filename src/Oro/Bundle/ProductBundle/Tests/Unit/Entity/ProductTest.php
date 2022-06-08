<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\ProductDescription;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductShortDescription;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ProductTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['sku', 'sku-test-01'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['primaryUnitPrecision',  null],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['status', Product::STATUS_ENABLED, Product::STATUS_DISABLED],
            ['type', Product::TYPE_CONFIGURABLE, Product::TYPE_SIMPLE],
            ['attributeFamily', new AttributeFamily()],
            ['slugPrototypesWithRedirect', new SlugPrototypesWithRedirect(new ArrayCollection(), false), false],
            ['featured', true, false],
            ['newArrival', true, false],
        ];

        self::assertPropertyAccessors(new Product(), $properties);
    }

    public function testCollections(): void
    {
        $collections = [
            ['names', new ProductName()],
            ['descriptions', new ProductDescription()],
            ['shortDescriptions', new ProductShortDescription()],
            ['images', new ProductImage()],
            ['slugPrototypes', new LocalizedFallbackValue()],
            ['slugs', new Slug()],
            ['variantLinks', new ProductVariantLink()],
            ['parentVariantLinks', new ProductVariantLink()],
            ['kitItems', new ProductKitItem()],
        ];

        self::assertPropertyCollections(new Product(), $collections);
    }

    public function testToString(): void
    {
        $product = new Product();
        self::assertSame('', (string)$product);

        $product->setSku(123);
        self::assertSame('123', (string)$product);

        $product->addName((new ProductName())->setString('localized_name'));
        self::assertEquals('localized_name', (string)$product);
    }

    public function testPrePersist(): void
    {
        $product = new Product();
        $this->addDefaultName($product, 'default');
        $product->prePersist();
        self::assertInstanceOf(\DateTime::class, $product->getCreatedAt());
        self::assertInstanceOf(\DateTime::class, $product->getUpdatedAt());
    }

    public function testPrePersistWithoutDefaultName(): void
    {
        $product = new Product();
        $this->expectException(\RuntimeException::class);
        $product->prePersist();
    }

    public function testPrePersistWithMultibyteChars(): void
    {
        $product = new Product();
        $this->addDefaultName($product, 'default');

        $product->setSku('Aбв123');
        $product->prePersist();
        self::assertEquals('Aбв123', $product->getSku());
        self::assertEquals('AБВ123', $product->getSkuUppercase());
    }

    public function testPreUpdate(): void
    {
        $product = new Product();
        $product->setSku('sample-sku');
        $product->setType(Product::TYPE_SIMPLE);
        $product->setVariantFields(['field']);
        $product->addVariantLink(new ProductVariantLink(new Product(), new Product()));
        $this->addDefaultName($product, 'default');

        $product->preUpdate();

        self::assertInstanceOf(\DateTime::class, $product->getUpdatedAt());
        self::assertCount(0, $product->getVariantFields());
        self::assertEquals('SAMPLE-SKU', $product->getSkuUppercase());
    }

    public function testPreUpdateWithoutDefaultName(): void
    {
        $product = new Product();
        $product->setType(Product::TYPE_SIMPLE);
        $product->setVariantFields(['field']);
        $product->addVariantLink(new ProductVariantLink(new Product(), new Product()));

        $this->expectException(\RuntimeException::class);
        $product->preUpdate();
    }

    public function testUnitRelation(): void
    {
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit((new ProductUnit())->setCode('kg'));
        $unitPrecision->setPrecision(0);

        $product = new Product();

        self::assertCount(0, $product->getUnitPrecisions());

        // Add new ProductUnitPrecision
        self::assertSame($product, $product->addAdditionalUnitPrecision($unitPrecision));
        self::assertCount(1, $product->getUnitPrecisions());

        $actual = $product->getUnitPrecisions();
        self::assertInstanceOf(ArrayCollection::class, $actual);
        self::assertEquals([$unitPrecision], $actual->toArray());

        // Add already added ProductUnitPrecision
        self::assertSame($product, $product->addAdditionalUnitPrecision($unitPrecision));
        self::assertCount(1, $product->getUnitPrecisions());

        // Remove ProductUnitPrecision
        self::assertSame($product, $product->removeAdditionalUnitPrecision($unitPrecision));
        self::assertCount(0, $product->getUnitPrecisions());

        $actual = $product->getUnitPrecisions();
        self::assertInstanceOf(ArrayCollection::class, $actual);
        self::assertNotContains($unitPrecision, $actual->toArray());
    }

    public function testImagesRelation(): void
    {
        $productImage = new ProductImage();
        $product = new Product();

        self::assertCount(0, $product->getImages());

        self::assertSame($product, $product->addImage($productImage));
        self::assertCount(1, $product->getImages());

        $actual = $product->getImages();
        self::assertInstanceOf(ArrayCollection::class, $actual);
        self::assertEquals([$productImage], $actual->toArray());

        self::assertSame($product, $product->addImage($productImage));
        self::assertCount(1, $product->getImages());

        self::assertSame($product, $product->removeImage($productImage));
        self::assertCount(0, $product->getImages());

        $actual = $product->getImages();
        self::assertInstanceOf(ArrayCollection::class, $actual);
        self::assertNotContains($productImage, $actual->toArray());
    }

    public function testGetUnitPrecisionByUnitCode(): void
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
        $product->addAdditionalUnitPrecision($unitPrecision);

        self::assertNull($product->getUnitPrecision('item'));
        self::assertEquals($unitPrecision, $product->getUnitPrecision('kg'));
    }

    public function testGetAvailableUnitCodes(): void
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
        $product->setPrimaryUnitPrecision($unitPrecision);

        self::assertEquals(['kg'], $product->getAvailableUnitCodes());
    }

    public function testGetAvailableUnitsPrecision(): void
    {
        [$kgPrecision, $itemPrecision] = $this->prepareUnitsPrecision();

        $product = new Product();
        $product->setPrimaryUnitPrecision($kgPrecision)->addUnitPrecision($itemPrecision);

        self::assertEquals(['kg' => 1, 'item' => 0], $product->getAvailableUnitsPrecision());
    }

    public function testGetSellUnitsPrecision(): void
    {
        [$kgPrecision, $itemPrecision] = $this->prepareUnitsPrecision();

        $product = new Product();
        $product->setPrimaryUnitPrecision($kgPrecision)->addUnitPrecision($itemPrecision);

        self::assertEquals(['kg' => 1], $product->getSellUnitsPrecision());
    }

    private function prepareUnitsPrecision(): array
    {
        $kgUnit = new ProductUnit();
        $kgUnit->setCode('kg')->setDefaultPrecision(3);
        $itemUnit = new ProductUnit();
        $itemUnit->setCode('item')->setDefaultPrecision(0);

        $kgPrecision = new ProductUnitPrecision();
        $kgPrecision->setUnit($kgUnit)->setPrecision(1);
        $itemPrecision = new ProductUnitPrecision();
        $itemPrecision->setUnit($itemUnit)->setPrecision(0)->setSell(false);

        return [$kgPrecision, $itemPrecision];
    }

    public function testGetAvailableUnits(): void
    {
        $unit = new ProductUnit();
        $unit
            ->setCode('itm')
            ->setDefaultPrecision(3);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setUnit($unit)
            ->setPrecision($unit->getDefaultPrecision());

        $product = new Product();
        $product->addUnitPrecision($unitPrecision);

        self::assertEquals(['itm' => $unit], $product->getAvailableUnits());
    }

    public function testClone(): void
    {
        $id = 123;
        $unit = new ProductUnit();
        $unit->setCode('kg')->setDefaultPrecision(3);
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);
        $product = new Product();
        ReflectionUtil::setId($product, $id);
        $product->addAdditionalUnitPrecision($unitPrecision);
        $product->getNames()->add(new LocalizedFallbackValue());
        $product->getDescriptions()->add(new LocalizedFallbackValue());
        $product->getShortDescriptions()->add(new LocalizedFallbackValue());
        $product->addVariantLink(new ProductVariantLink(new Product(), new Product()));
        $product->setVariantFields(['field']);
        $product->addImage(new ProductImage());
        $product->addSlugPrototype(new LocalizedFallbackValue());
        $product->addSlug(new Slug());

        self::assertEquals($id, $product->getId());
        self::assertCount(1, $product->getUnitPrecisions());
        self::assertCount(1, $product->getNames());
        self::assertCount(1, $product->getDescriptions());
        self::assertCount(1, $product->getShortDescriptions());
        self::assertCount(1, $product->getImages());
        self::assertCount(1, $product->getVariantLinks());
        self::assertCount(1, $product->getVariantFields());
        self::assertCount(1, $product->getSlugPrototypes());
        self::assertCount(1, $product->getSlugs());

        $productCopy = clone $product;

        self::assertNull($productCopy->getId());
        self::assertEmpty($productCopy->getUnitPrecisions());
        self::assertEmpty($productCopy->getNames());
        self::assertEmpty($productCopy->getDescriptions());
        self::assertEmpty($productCopy->getShortDescriptions());
        self::assertEmpty($productCopy->getImages());
        self::assertEmpty($productCopy->getVariantLinks());
        self::assertEmpty($productCopy->getVariantFields());
        self::assertEmpty($productCopy->getSlugPrototypes());
        self::assertEmpty($productCopy->getSlugs());
    }

    public function testGetDefaultName(): void
    {
        $defaultName = 'default';
        $product = new Product();
        $this->addDefaultName($product, $defaultName);

        $localizedName = new ProductName();
        $localizedName->setString('localized')
            ->setLocalization(new Localization());

        $product->addName($localizedName);

        self::assertEquals($defaultName, $product->getDefaultName());
    }

    public function testNoDefaultName(): void
    {
        $product = new Product();
        self::assertNull($product->getDefaultName());
    }

    public function testGetDefaultDescription(): void
    {
        $defaultDescription = new ProductDescription();
        $defaultDescription->setString('default');

        $localizedDescription = new ProductDescription();
        $localizedDescription->setString('localized')
            ->setLocalization(new Localization());

        $product = new Product();
        $product->addDescription($defaultDescription)
            ->addDescription($localizedDescription);

        self::assertEquals($defaultDescription, $product->getDefaultDescription());
    }

    public function testGetDefaultShortDescription(): void
    {
        $defaultShortDescription = new ProductShortDescription();
        $defaultShortDescription->setString('default short');

        $localizedShortDescription = new ProductShortDescription();
        $localizedShortDescription->setString('localized')->setLocalization(new Localization());

        $product = new Product();
        $product->addShortDescription($defaultShortDescription)->addShortDescription($localizedShortDescription);

        self::assertEquals($defaultShortDescription, $product->getDefaultShortDescription());
    }

    public function testVariantLinksRelation(): void
    {
        $variantLink = new ProductVariantLink(new Product(), new Product());
        $product = new Product();

        self::assertCount(0, $product->getVariantLinks());

        self::assertSame($product, $product->addVariantLink($variantLink));
        self::assertCount(1, $product->getVariantLinks());

        $actual = $product->getVariantLinks();
        self::assertInstanceOf(ArrayCollection::class, $actual);
        self::assertEquals([$variantLink], $actual->toArray());

        // Add already added variant link
        self::assertSame($product, $product->addVariantLink($variantLink));
        self::assertCount(1, $product->getVariantLinks());

        // Remove variant link
        self::assertSame($product, $product->removeVariantLink($variantLink));
        self::assertCount(0, $product->getVariantLinks());

        $actual = $product->getVariantLinks();
        self::assertInstanceOf(ArrayCollection::class, $actual);
        self::assertNotContains($variantLink, $actual->toArray());
    }

    public function getDefaultDescriptionExceptionDataProvider(): array
    {
        return [
            'several default descriptions' => [[new LocalizedFallbackValue(), new LocalizedFallbackValue()]],
        ];
    }

    public function testGetStatuses(): void
    {
        self::assertIsArray(Product::getStatuses());
        self::assertNotEmpty(Product::getStatuses());
    }

    public function testUnitPrecisions(): void
    {
        $product = new Product();
        $unitPrecision1 = $this->createUnitPrecision('kg', 3);
        $unitPrecision2 = $this->createUnitPrecision('piece', 1);
        $unitPrecision3 = $this->createUnitPrecision('set', 1);

        $product->setPrimaryUnitPrecision($unitPrecision1);
        $product->addAdditionalUnitPrecision($unitPrecision2);
        $product->addAdditionalUnitPrecision($unitPrecision3);

        $expectedAdditionalUnits = [$unitPrecision1, $unitPrecision2, $unitPrecision3];
        $actualAdditionalUnits = $product->getUnitPrecisions()->toArray();

        self::assertEquals($expectedAdditionalUnits, array_values($actualAdditionalUnits));
    }

    private function createUnitPrecision(string $code, int $precisionValue): ProductUnitPrecision
    {
        $unit = new ProductUnit();
        $unit
            ->setCode($code)
            ->setDefaultPrecision($precisionValue);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setUnit($unit)
            ->setPrecision($unit->getDefaultPrecision());

        return $unitPrecision;
    }

    public function testGetImagesByType(): void
    {
        $product = new Product();

        self::assertCount(0, $product->getImagesByType('main'));

        $image1 = new ProductImage();
        $image1->addType('main');
        $image1->addType('additional');

        $image2 = new ProductImage();
        $image2->addType('main');

        $product->addImage($image1);
        $product->addImage($image2);

        self::assertCount(2, $product->getImagesByType('main'));
        self::assertCount(1, $product->getImagesByType('additional'));
    }

    public function testIsConfigurable(): void
    {
        $simpleProduct = new Product();

        $configurableProduct = new Product();
        $configurableProduct->setType(Product::TYPE_CONFIGURABLE);

        self::assertFalse($simpleProduct->isConfigurable());
        self::assertTrue($configurableProduct->isConfigurable());
    }

    public function testIsSimple(): void
    {
        $simpleProduct = new Product();

        $configurableProduct = new Product();
        $configurableProduct->setType(Product::TYPE_CONFIGURABLE);

        self::assertFalse($configurableProduct->isSimple());
        self::assertTrue($simpleProduct->isSimple());
    }

    public function testGetTypes(): void
    {
        self::assertEquals([Product::TYPE_SIMPLE, Product::TYPE_CONFIGURABLE, Product::TYPE_KIT], Product::getTypes());
    }

    public function testAddVariantLink(): void
    {
        $productSimple = new Product();
        $productSimple->setId(1);

        $parentProduct = new Product();
        $parentProduct->setId(2);

        $variantLink1 = $this->getMockBuilder(ProductVariantLink::class)
            ->setConstructorArgs([$parentProduct, $productSimple])
            ->onlyMethods(['setParentProduct'])
            ->getMock();
        $variantLink1->expects(self::never())
            ->method('setParentProduct');

        $variantLink2 = $this->getMockBuilder(ProductVariantLink::class)
            ->onlyMethods(['setParentProduct'])
            ->getMock();
        $variantLink2->expects(self::once())
            ->method('setParentProduct');

        $product = new Product();
        $product->addVariantLink($variantLink1);
        $product->addVariantLink($variantLink2);
    }

    public function testAddParentVariantLink(): void
    {
        $productSimple = new Product();
        $productSimple->setId(1);

        $parentProduct = new Product();
        $parentProduct->setId(2);

        $variantLink1 = $this->getMockBuilder(ProductVariantLink::class)
            ->setConstructorArgs([$parentProduct, $productSimple])
            ->onlyMethods(['setParentProduct'])
            ->getMock();
        $variantLink1->expects(self::never())
            ->method('setParentProduct');

        $variantLink2 = $this->getMockBuilder(ProductVariantLink::class)
            ->onlyMethods(['setProduct'])
            ->getMock();
        $variantLink2->expects(self::once())
            ->method('setProduct');

        $product = new Product();
        $product->addParentVariantLink($variantLink1);
        $product->addParentVariantLink($variantLink2);
    }

    private function addDefaultName(Product $product, string $name): void
    {
        $defaultName = new ProductName();
        $defaultName->setString($name);

        $product->addName($defaultName);
    }
}
