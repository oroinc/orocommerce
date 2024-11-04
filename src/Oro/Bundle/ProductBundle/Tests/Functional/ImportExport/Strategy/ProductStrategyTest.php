<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ImportExport\Strategy;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\ImportExport\Strategy\ProductStrategy;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitForAdditionalOrganizationData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * @dbIsolationPerTest
 */
class ProductStrategyTest extends WebTestCase
{
    private ProductStrategy $strategy;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductData::class,
            LoadOrganization::class,
            LoadBusinessUnit::class,
            LoadProductKitData::class,
            LoadProductKitForAdditionalOrganizationData::class
        ]);

        $container = $this->getContainer();

        $container->get('oro_importexport.field.database_helper')->onClear();
        $this->strategy = new ProductStrategy(
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.strategy.configurable_import_strategy_helper'),
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator'),
            $container->get('oro_importexport.strategy.new_entities_helper'),
            $container->get('oro_entity.doctrine_helper'),
            $container->get('oro_importexport.field.related_entity_state_helper')
        );
        $this->strategy->setEntityName(Product::class);
        $this->strategy->setVariantLinkClass(ProductVariantLink::class);
        $this->strategy->setLocalizedFallbackValueClass(LocalizedFallbackValue::class);
        $this->strategy->setTokenAccessor($container->get('oro_security.token_accessor'));
        $this->strategy->setOwnershipSetter($container->get('oro_organization.entity_ownership_associations_setter'));
    }

    /**
     * Impossible to import product when it's variant is in the same batch.
     * Caused because variant is not in DB it could not be loaded by SKU and variant link contains invalid relation.
     *
     * @link https://magecore.atlassian.net/browse/BB-7908
     */
    public function testProcessWithVariantLinks()
    {
        $context = new Context([]);
        $context->setValue('itemData', []);
        $this->strategy->setImportExportContext($context);

        $inventoryStatus = $this->getInventoryStatus();

        /** @var ProductUnit $unit */
        $unit = $this->getReference(LoadProductUnits::BOX);
        $attributeFamily = $this->createAttributeFamily('default_family');
        $newProductSku = 'PR-V1';

        // Prepare new product that is imported in same batch and will be used later as variant link
        $newProduct = $this->createProduct($newProductSku, $attributeFamily, $unit, $inventoryStatus);
        /** @var Product $processedNewProduct */
        $processedNewProduct = $this->strategy->process($newProduct);
        $this->assertEquals([], $context->getErrors());
        $this->assertInstanceOf(Product::class, $processedNewProduct);
        $this->assertSame($newProductSku, $processedNewProduct->getSku());

        // Get existing product that should be found by SKU as variant link relation
        /** @var Product $existingProduct */
        $existingProduct = $this->getReference(LoadProductData::PRODUCT_1);

        $linkToNewProduct = new ProductVariantLink();
        $linkToNewProduct->setProduct((new Product())->setSku($newProductSku));
        $linkToExistingProduct = new ProductVariantLink();
        $linkToExistingProduct->setProduct((new Product())->setSku($existingProduct->getSku()));

        // Add prepared variant links to newly imported product
        $productWithVariants = $this->createProduct('PR-VV', $attributeFamily, $unit, $inventoryStatus);
        $productWithVariants->addVariantLink($linkToNewProduct);
        $productWithVariants->addVariantLink($linkToExistingProduct);

        // Check that all variant links present and were attached correctly
        /** @var Product $processedProductWithVariants */
        $processedProductWithVariants = $this->strategy->process($productWithVariants);
        $this->assertEquals([], $context->getErrors());
        $this->assertInstanceOf(Product::class, $processedProductWithVariants);
        $this->assertSame($productWithVariants->getSku(), $processedProductWithVariants->getSku());
        $this->assertCount(2, $processedProductWithVariants->getVariantLinks());
        $usedVariantLinksProductSkus = array_map(
            function (ProductVariantLink $variantLink) {
                return $variantLink->getProduct()->getSku();
            },
            $processedProductWithVariants->getVariantLinks()->toArray()
        );
        $this->assertContains($newProductSku, $usedVariantLinksProductSkus);
        $this->assertContains($existingProduct->getSku(), $usedVariantLinksProductSkus);
    }

    public function testProcessCreateNewKitItemsByIgnoringID(): void
    {
        $context = new Context([]);
        $context->setValue('itemData', []);
        $this->strategy->setImportExportContext($context);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $kitItem = $productKit->getKitItems()->current();

        $product->setSku('TEST_KIT');
        $product->setType(Product::TYPE_KIT);
        $product->addKitItem($kitItem);
        $kitItem->setProductKit($product);

        $this->getContainer()->get('oro_entity.helper.field_helper')->setObjectValue($product, 'id', null);

        $this->assertNotEmpty($kitItem->getId());

        $this->strategy->process($product);

        $this->assertEquals(null, $kitItem->getId());
    }

    public function testBeforeProcessPostponeProductKitRow(): void
    {
        $context = new Context([]);
        $context->setValue('rawItemData', ['sku' => 'SKU']);
        $this->strategy->setImportExportContext($context);

        $productKitItem = new ProductKitItemProduct();
        $productKitItem->setProduct((new ProductStub())->setSku('TEST'));

        /** @var Product $product */
        $product = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $kitItem = $product->getKitItems()->current();
        $kitItem->addKitItemProduct($productKitItem);

        $this->strategy->process($product);

        $this->assertEquals([['sku' => 'SKU']], $context->getPostponedRows());
        $this->assertEquals(0, $context->getValue('postponedRowsDelay'));
    }

    public function testProcessEnsureProductKitItemBelongsToProductOrganization(): void
    {
        $context = new Context([]);
        $context->setValue('itemData', []);
        $this->strategy->setImportExportContext($context);

        $productKit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $additionalKit = $this->getReference(LoadProductKitForAdditionalOrganizationData::ADDITIONAL_PRODUCT_KIT);

        foreach ($additionalKit->getKitItems() as $kitItem) {
            $productKit1->addKitItem($kitItem);
        }

        $message = sprintf('Error in row #0. Product Kit Item with #%s ID was not found.', $kitItem->getId());
        $this->assertCount(2, $productKit1->getKitItems());

        $this->strategy->process($productKit1);

        $this->assertCount(1, $productKit1->getKitItems());
        $this->assertEquals(null, $kitItem->getId());
        $this->assertEquals([$message], $context->getErrors());
    }

    public function testProcessWithEmptyAdditionalUnitPrecisionCode()
    {
        $context = new Context([]);
        $context->setValue('itemData', []);
        $this->strategy->setImportExportContext($context);
        /** @var ProductUnit $unit */
        $unit = $this->getReference(LoadProductUnits::BOX);
        $attributeFamily = $this->createAttributeFamily('default_family');
        $newProductSku = 'PR-V1';
        // Prepare new product that is imported in same batch and will be used later as variant link
        $newProduct = $this->createProduct(
            $newProductSku,
            $attributeFamily,
            $unit,
            $this->getInventoryStatus()
        );
        //Add invalid ProductUnitPrecision without unit
        $newProduct->getUnitPrecisions()->add((new ProductUnitPrecision()));
        /** @var Product $processedNewProduct */
        $processedNewProduct = $this->strategy->process($newProduct);
        $this->assertEquals([], $context->getErrors());
        $this->assertCount(1, $processedNewProduct->getUnitPrecisions());
    }

    public function testProcessWithDuplicateUnitPrecisionCode()
    {
        $context = new Context([]);
        $context->setValue('read_offset', 1);
        $context->setValue('itemData', [
            'additionalUnitPrecisions' => [
                [
                    'unit' => ['code' => 'duplicate_code'],
                    'precision' => 0
                ],
                [
                    'unit' => ['code' => 'duplicate_code'],
                    'precision' => 0
                ]
            ]
        ]);
        $this->strategy->setImportExportContext($context);
        /** @var ProductUnit $unit */
        $unit = $this->getReference(LoadProductUnits::BOX);
        $attributeFamily = $this->createAttributeFamily('default_family');
        $newProductSku = 'PR-V1';
        // Prepare new product that is imported in same batch and will be used later as variant link
        $newProduct = $this->createProduct(
            $newProductSku,
            $attributeFamily,
            $unit,
            $this->getInventoryStatus()
        );
        /** @var Product $processedNewProduct */
        $this->strategy->process($newProduct);
        $this->assertEquals(['Error in row #1. Each product unit code should be unique'], $context->getErrors());
    }

    public function testProcessNotEmptyConfigurableAttributes(): void
    {
        $context = new Context(['incremented_read' => true]);
        $context->setValue('read_offset', 1);
        $context->setValue('itemData', []);

        $this->strategy->setImportExportContext($context);

        /** @var ProductUnit $unit */
        $unit = $this->getReference(LoadProductUnits::BOX);

        $attributeFamily = $this->createAttributeFamily('default_family');

        // Prepare new product that is imported in same batch and will be used later as variant link
        $newProduct = $this->createProduct('PR-V2', $attributeFamily, $unit, $this->getInventoryStatus());
        $newProduct->setType(Product::TYPE_CONFIGURABLE);

        /** @var Product $processedNewProduct */
        $this->strategy->process($newProduct);
        $this->assertEquals(
            [
                'Error in row #1. Configurable product requires at least one filterable attribute ' .
                'of the Select or Boolean type to enable product variants. ' .
                'The provided product family does not fit for configurable products.'
            ],
            $context->getErrors()
        );
    }

    public function testProcessWhenProductHasNoTitle()
    {
        $context = new Context([]);
        $context->setValue('itemData', []);
        $this->strategy->setImportExportContext($context);

        $product = $this->strategy->process(new Product());

        $this->assertNull($product);
        $this->assertContains(
            'Error in row #0. Name Localization Name: Product default name is blank',
            $context->getErrors()
        );
    }

    private function getInventoryStatus(): EnumOptionInterface
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository(EnumOption::class)
            ->find(ExtendHelper::buildEnumOptionId('prod_inventory_status', 'in_stock'));
    }

    private function createProduct(
        string $sku,
        AttributeFamily $attributeFamily,
        ProductUnit $unit,
        EnumOptionInterface $inventoryStatus
    ): Product {
        $newProduct = new Product();
        $productName = new ProductName();
        $productName->setString($sku);
        $newProduct->addName($productName);

        $newProduct->setSku($sku);
        $newProduct->setAttributeFamily($attributeFamily);
        $newProduct->setInventoryStatus($inventoryStatus);
        $newProduct->setPrimaryUnitPrecision(
            (new ProductUnitPrecision())->setUnit($unit)
                ->setPrecision(0)
        );
        $newProduct->setOwner($this->getReference('business_unit'));
        $newProduct->setOrganization($this->getReference('organization'));

        return $newProduct;
    }

    private function createAttributeFamily(string $code): AttributeFamily
    {
        $family = new AttributeFamily();
        $family->setCode($code);

        return $family;
    }
}
