<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\Manager;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductFallbackData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @dbIsolationPerTest
 */
class ProductFallbackPopulationTest extends WebTestCase
{
    private const FALLBACK_ID_PER_FIELD = [
        'pageTemplate' => ThemeConfigurationFallbackProvider::FALLBACK_ID,
        'manageInventory' => CategoryFallbackProvider::FALLBACK_ID,
        'highlightLowInventory' => CategoryFallbackProvider::FALLBACK_ID,
        'inventoryThreshold' => CategoryFallbackProvider::FALLBACK_ID,
        'lowInventoryThreshold' => CategoryFallbackProvider::FALLBACK_ID,
        'backOrder' => CategoryFallbackProvider::FALLBACK_ID,
        'decrementQuantity' => CategoryFallbackProvider::FALLBACK_ID,
        'minimumQuantityToOrder' => CategoryFallbackProvider::FALLBACK_ID,
        'maximumQuantityToOrder' => CategoryFallbackProvider::FALLBACK_ID,
        'isUpcoming' => CategoryFallbackProvider::FALLBACK_ID,
    ];

    private ProductFallbackUpdateManager $manager;
    private PropertyAccessorInterface $propertyAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductFallbackData::class]);

        $this->manager = self::getContainer()->get('oro_product.manager.fallback_update');
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function testEveryEmptyFieldIsPopulatedWithTheFallbackIdOfItsProvider(): void
    {
        $product = $this->getProduct(LoadProductFallbackData::PRODUCT_WITHOUT_CATEGORY);

        self::assertSame(1, $this->manager->processChunk([$product->getId()]));

        $product = $this->refresh($product);
        foreach (self::FALLBACK_ID_PER_FIELD as $field => $expectedFallbackId) {
            $value = $this->propertyAccessor->getValue($product, $field);
            self::assertInstanceOf(EntityFieldFallbackValue::class, $value, $field);
            self::assertSame($expectedFallbackId, $value->getFallback(), $field);
            // The empty value has to be readable back through the mapping of the entity.
            self::assertNull($value->getArrayValue(), $field);
            self::assertNull($value->getScalarValue(), $field);
        }
    }

    public function testAlreadyPopulatedFieldsAreKept(): void
    {
        $product = $this->getProduct(LoadProductFallbackData::PRODUCT_PARTIAL);
        $keptScalar = $this->propertyAccessor->getValue($product, 'manageInventory')->getId();
        $keptArray = $this->propertyAccessor->getValue($product, 'pageTemplate')->getId();

        self::assertSame(1, $this->manager->processChunk([$product->getId()]));

        $product = $this->refresh($product);
        $manageInventory = $this->propertyAccessor->getValue($product, 'manageInventory');
        $pageTemplate = $this->propertyAccessor->getValue($product, 'pageTemplate');

        self::assertSame($keptScalar, $manageInventory->getId());
        self::assertSame($keptArray, $pageTemplate->getId());
        self::assertEquals(LoadProductFallbackData::PARTIAL_MANAGE_INVENTORY, $manageInventory->getScalarValue());
        self::assertSame(LoadProductFallbackData::PARTIAL_PAGE_TEMPLATE, $pageTemplate->getArrayValue());

        foreach (array_diff(array_keys(self::FALLBACK_ID_PER_FIELD), ['manageInventory', 'pageTemplate']) as $field) {
            self::assertInstanceOf(
                EntityFieldFallbackValue::class,
                $this->propertyAccessor->getValue($product, $field),
                $field
            );
        }
    }

    public function testResultCountsProductsAndNotCreatedValues(): void
    {
        $ids = [
            $this->getProduct(LoadProductFallbackData::PRODUCT_WITHOUT_CATEGORY)->getId(),
            $this->getProduct(LoadProductFallbackData::PRODUCT_NESTED)->getId(),
            $this->getProduct(LoadProductFallbackData::PRODUCT_PARTIAL)->getId(),
            $this->getProduct(LoadProductFallbackData::PRODUCT_FILLED)->getId(),
        ];

        // Three products have at least one empty field, the fourth one is fully populated already.
        self::assertSame(3, $this->manager->processChunk($ids));
    }

    public function testRepeatedRunChangesNothing(): void
    {
        $product = $this->getProduct(LoadProductFallbackData::PRODUCT_WITHOUT_CATEGORY);

        $this->manager->processChunk([$product->getId()]);
        $idsAfterFirstRun = $this->collectFallbackValueIds($this->refresh($product));

        self::assertSame(0, $this->manager->processChunk([$product->getId()]));
        self::assertSame($idsAfterFirstRun, $this->collectFallbackValueIds($this->refresh($product)));
    }

    public function testValueIsResolvedThroughSeveralLevelsOfFallbacks(): void
    {
        $product = $this->getProduct(LoadProductFallbackData::PRODUCT_NESTED);
        $resolver = $this->getResolver();

        // The system configuration default is false and none of the categories of the product owns a value
        // that is reachable before the population.
        self::assertFalse((bool)$resolver->getFallbackValue($product, 'manageInventory'));

        self::assertSame(1, $this->manager->processChunk([$product->getId()]));

        // category_1_2_3 -> category_1_2 -> category_1, which owns the value.
        self::assertEquals(
            LoadProductFallbackData::NESTED_MANAGE_INVENTORY,
            $resolver->getFallbackValue($this->refresh($product), 'manageInventory')
        );
    }

    private function getProduct(string $reference): Product
    {
        return $this->getReference($reference);
    }

    private function refresh(Product $product): Product
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(Product::class);
        $em->clear();

        return $em->find(Product::class, $product->getId());
    }

    private function getResolver(): EntityFallbackResolver
    {
        return self::getContainer()->get('oro_entity.fallback.resolver.entity_fallback_resolver');
    }

    /**
     * @return array<string, int>
     */
    private function collectFallbackValueIds(Product $product): array
    {
        $ids = [];
        foreach (array_keys(self::FALLBACK_ID_PER_FIELD) as $field) {
            $ids[$field] = $this->propertyAccessor->getValue($product, $field)->getId();
        }

        return $ids;
    }
}
