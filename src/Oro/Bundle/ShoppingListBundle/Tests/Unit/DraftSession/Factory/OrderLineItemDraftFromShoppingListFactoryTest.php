<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\DraftSession\Factory;

use Oro\Bundle\EntityExtendBundle\Test\ExtendedEntityTestTrait;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ShoppingListBundle\DraftSession\Factory\OrderLineItemDraftFromShoppingListFactory;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ProductKitItemLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDraftFromShoppingListFactoryTest extends TestCase
{
    use ExtendedEntityTestTrait;

    private OrderLineItemDraftFromShoppingListFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);

        $this->factory = new OrderLineItemDraftFromShoppingListFactory(
            $draftSyncReferenceResolver,
        );

        $draftSyncReferenceResolver->expects(self::any())
            ->method('getReference')
            ->willReturnArgument(0);

        // ProductKitItem::getDefaultLabel() is an extended method called by updateKitItemFallbackFields()
        // when setKitItem() is invoked on the target kit item line item. Stub it to return null.
        $this->entityFieldTestExtension->addExpectation(
            ProductKitItem::class,
            'getDefaultLabel',
            static function (array $arguments, object $object, mixed &$result): bool {
                $result = null;

                return true;
            }
        );
    }

    public function testSupportsWhenLineItem(): void
    {
        self::assertTrue($this->factory->supports(LineItem::class));
    }

    public function testSupportsWhenLineItemSubclass(): void
    {
        self::assertTrue($this->factory->supports(LineItemStub::class));
    }

    public function testSupportsWhenNotLineItem(): void
    {
        self::assertFalse($this->factory->supports(ShoppingList::class));
    }

    public function testSupportsWhenOrderLineItem(): void
    {
        self::assertFalse($this->factory->supports(OrderLineItem::class));
    }

    public function testCreateDraftMapsFields(): void
    {
        $draftSessionUuid = 'draft-session-uuid';

        $product = new Product();
        $product->setSku('SKU-1');
        $productUnit = (new ProductUnit())->setCode('each');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setQuantity(12.3456);
        $lineItem->setNotes('Line item notes');

        $orderLineItemDraft = $this->factory->createDraft($lineItem, $draftSessionUuid);

        self::assertInstanceOf(OrderLineItem::class, $orderLineItemDraft);
        self::assertSame($draftSessionUuid, $orderLineItemDraft->getDraftSessionUuid());
        self::assertSame($product, $orderLineItemDraft->getProduct());
        self::assertSame('SKU-1', $orderLineItemDraft->getProductSku());
        self::assertSame($productUnit, $orderLineItemDraft->getProductUnit());
        self::assertSame('each', $orderLineItemDraft->getProductUnitCode());
        self::assertSame(12.3456, $orderLineItemDraft->getQuantity());
        self::assertSame('Line item notes', $orderLineItemDraft->getComment());
        self::assertCount(0, $orderLineItemDraft->getKitItemLineItems());
    }

    public function testCreateDraftUsesLineItemProductUnitOverPrimaryUnitPrecision(): void
    {
        $primaryUnitPrecision = (new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('item'));
        $product = new Product();
        $product->setSku('SKU-1');
        $product->setPrimaryUnitPrecision($primaryUnitPrecision);

        $productUnit = (new ProductUnit())->setCode('each');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);

        $orderLineItemDraft = $this->factory->createDraft($lineItem, 'draft-session-uuid');

        self::assertSame($productUnit, $orderLineItemDraft->getProductUnit());
        self::assertSame('each', $orderLineItemDraft->getProductUnitCode());
    }

    public function testCreateDraftFallsBackToPrimaryUnitPrecisionWhenLineItemHasNoProductUnit(): void
    {
        $primaryUnit = (new ProductUnit())->setCode('item');
        $product = new Product();
        $product->setSku('SKU-1');
        $product->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($primaryUnit));

        $lineItem = new LineItem();
        $lineItem->setProduct($product);

        $orderLineItemDraft = $this->factory->createDraft($lineItem, 'draft-session-uuid');

        self::assertSame($primaryUnit, $orderLineItemDraft->getProductUnit());
        self::assertSame('item', $orderLineItemDraft->getProductUnitCode());
    }

    public function testCreateDraftWhenNoProductUnitAndNoPrimaryUnitPrecision(): void
    {
        $product = new Product();
        $product->setSku('SKU-1');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);

        $orderLineItemDraft = $this->factory->createDraft($lineItem, 'draft-session-uuid');

        // The product unit cannot be resolved, so it is left empty instead of failing.
        self::assertSame($product, $orderLineItemDraft->getProduct());
        self::assertSame('SKU-1', $orderLineItemDraft->getProductSku());
        self::assertNull($orderLineItemDraft->getProductUnit());
        self::assertNull($orderLineItemDraft->getProductUnitCode());
    }

    /**
     * @dataProvider emptyQuantityDataProvider
     */
    public function testCreateDraftWhenQuantityIsEmpty(?float $quantity): void
    {
        $product = new Product();
        $product->setSku('SKU-1');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit((new ProductUnit())->setCode('each'));
        $lineItem->setQuantity($quantity);

        $orderLineItemDraft = $this->factory->createDraft($lineItem, 'draft-session-uuid');

        self::assertSame(1, $orderLineItemDraft->getQuantity());
    }

    public static function emptyQuantityDataProvider(): iterable
    {
        yield 'zero quantity' => [0.0];
        yield 'null quantity' => [null];
    }

    public function testCreateDraftWhenNoNotes(): void
    {
        $product = new Product();
        $product->setSku('SKU-1');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit((new ProductUnit())->setCode('each'));

        $orderLineItemDraft = $this->factory->createDraft($lineItem, 'draft-session-uuid');

        self::assertNull($orderLineItemDraft->getComment());
    }

    public function testCreateDraftUsesResolvedReferences(): void
    {
        $product = new Product();
        $product->setSku('SKU-1');
        $productUnit = (new ProductUnit())->setCode('each');

        $productReference = new Product();
        $productReference->setSku('SKU-REFERENCE');
        $productUnitReference = (new ProductUnit())->setCode('set');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);

        $draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);
        $draftSyncReferenceResolver->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnMap([
                [$product, $productReference],
                [$productUnit, $productUnitReference],
            ]);

        $factory = new OrderLineItemDraftFromShoppingListFactory($draftSyncReferenceResolver);

        $orderLineItemDraft = $factory->createDraft($lineItem, 'draft-session-uuid');

        self::assertSame($productReference, $orderLineItemDraft->getProduct());
        self::assertSame($productUnitReference, $orderLineItemDraft->getProductUnit());
        // The product SKU is taken from the shopping list line item, the unit code from the resolved reference.
        self::assertSame('SKU-1', $orderLineItemDraft->getProductSku());
        self::assertSame('set', $orderLineItemDraft->getProductUnitCode());
    }

    public function testCreateDraftFallsBackToPrimaryUnitPrecisionOfResolvedProductReference(): void
    {
        $product = new Product();
        $product->setSku('SKU-1');
        $product->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('item')));

        $referencePrimaryUnit = (new ProductUnit())->setCode('kg');
        $productReference = new Product();
        $productReference->setSku('SKU-REFERENCE');
        $productReference->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($referencePrimaryUnit));
        $referencePrimaryUnitReference = (new ProductUnit())->setCode('set');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);

        $draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);
        $draftSyncReferenceResolver->expects(self::exactly(3))
            ->method('getReference')
            ->willReturnMap([
                [$product, $productReference],
                [null, null],
                [$referencePrimaryUnit, $referencePrimaryUnitReference],
            ]);

        $factory = new OrderLineItemDraftFromShoppingListFactory($draftSyncReferenceResolver);

        $orderLineItemDraft = $factory->createDraft($lineItem, 'draft-session-uuid');

        // The primary unit precision is read from the resolved product reference, and its unit is resolved as well.
        self::assertSame($referencePrimaryUnitReference, $orderLineItemDraft->getProductUnit());
        self::assertSame('set', $orderLineItemDraft->getProductUnitCode());
    }

    public function testCreateDraftWhenLineItemHasNoProduct(): void
    {
        $productUnit = (new ProductUnit())->setCode('each');

        $lineItem = new LineItem();
        $lineItem->setProductUnit($productUnit);

        $orderLineItemDraft = $this->factory->createDraft($lineItem, 'draft-session-uuid');

        self::assertNull($orderLineItemDraft->getProduct());
        self::assertNull($orderLineItemDraft->getProductSku());
        self::assertSame($productUnit, $orderLineItemDraft->getProductUnit());
        self::assertSame('each', $orderLineItemDraft->getProductUnitCode());
    }

    public function testCreateDraftWhenLineItemHasNeitherProductNorProductUnit(): void
    {
        $lineItem = new LineItem();

        $orderLineItemDraft = $this->factory->createDraft($lineItem, 'draft-session-uuid');

        // Neither the product nor the unit can be resolved, so both are left empty instead of failing.
        self::assertNull($orderLineItemDraft->getProduct());
        self::assertNull($orderLineItemDraft->getProductSku());
        self::assertNull($orderLineItemDraft->getProductUnit());
        self::assertNull($orderLineItemDraft->getProductUnitCode());
    }

    public function testCreateDraftSynchronizesKitItemLineItems(): void
    {
        $draftSessionUuid = 'draft-session-uuid';

        $firstKitItem = new ProductKitItem();
        ReflectionUtil::setId($firstKitItem, 10);
        $firstKitItem->setSortOrder(5);
        $firstKitItemProduct = new Product();
        $firstKitItemProduct->setSku('KIT-SKU-1');
        $firstKitItemProductUnit = (new ProductUnit())->setCode('set');

        $firstSourceKitItemLineItem = new ProductKitItemLineItem();
        $firstSourceKitItemLineItem->setKitItem($firstKitItem);
        $firstSourceKitItemLineItem->setProduct($firstKitItemProduct);
        $firstSourceKitItemLineItem->setQuantity(3.0);
        $firstSourceKitItemLineItem->setUnit($firstKitItemProductUnit);
        // Deliberately different from the sort order of the kit item, see the assertions below.
        $firstSourceKitItemLineItem->setSortOrder(101);

        $secondKitItem = new ProductKitItem();
        ReflectionUtil::setId($secondKitItem, 20);
        $secondKitItem->setSortOrder(2);
        $secondKitItemProduct = new Product();
        $secondKitItemProduct->setSku('KIT-SKU-2');
        $secondKitItemProductUnit = (new ProductUnit())->setCode('piece');

        $secondSourceKitItemLineItem = new ProductKitItemLineItem();
        $secondSourceKitItemLineItem->setKitItem($secondKitItem);
        $secondSourceKitItemLineItem->setProduct($secondKitItemProduct);
        $secondSourceKitItemLineItem->setQuantity(7.0);
        $secondSourceKitItemLineItem->setUnit($secondKitItemProductUnit);
        $secondSourceKitItemLineItem->setSortOrder(202);

        $product = new Product();
        $product->setSku('SKU-1');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit((new ProductUnit())->setCode('each'));
        $lineItem->addKitItemLineItem($firstSourceKitItemLineItem);
        $lineItem->addKitItemLineItem($secondSourceKitItemLineItem);

        $orderLineItemDraft = $this->factory->createDraft($lineItem, $draftSessionUuid);

        $targetKitItemLineItems = $orderLineItemDraft->getKitItemLineItems();
        self::assertCount(2, $targetKitItemLineItems);

        /** @var OrderProductKitItemLineItem $firstTargetKitItemLineItem */
        $firstTargetKitItemLineItem = $targetKitItemLineItems->get(10);
        self::assertSame($draftSessionUuid, $firstTargetKitItemLineItem->getDraftSessionUuid());
        self::assertSame($firstKitItem, $firstTargetKitItemLineItem->getKitItem());
        self::assertSame($firstKitItemProduct, $firstTargetKitItemLineItem->getProduct());
        self::assertSame(3.0, $firstTargetKitItemLineItem->getQuantity());
        self::assertSame($firstKitItemProductUnit, $firstTargetKitItemLineItem->getProductUnit());
        self::assertSame($orderLineItemDraft, $firstTargetKitItemLineItem->getLineItem());
        // The sort order comes from the source kit item line item, not from its kit item (5).
        self::assertSame(101, $firstTargetKitItemLineItem->getSortOrder());

        /** @var OrderProductKitItemLineItem $secondTargetKitItemLineItem */
        $secondTargetKitItemLineItem = $targetKitItemLineItems->get(20);
        self::assertSame($draftSessionUuid, $secondTargetKitItemLineItem->getDraftSessionUuid());
        self::assertSame($secondKitItem, $secondTargetKitItemLineItem->getKitItem());
        self::assertSame($secondKitItemProduct, $secondTargetKitItemLineItem->getProduct());
        self::assertSame(7.0, $secondTargetKitItemLineItem->getQuantity());
        self::assertSame($secondKitItemProductUnit, $secondTargetKitItemLineItem->getProductUnit());
        self::assertSame($orderLineItemDraft, $secondTargetKitItemLineItem->getLineItem());
        self::assertSame(202, $secondTargetKitItemLineItem->getSortOrder());
    }

    public function testCreateDraftSetsKitItemLineItemSortOrderFromSourceKitItemLineItem(): void
    {
        $kitItem = new ProductKitItem();
        ReflectionUtil::setId($kitItem, 10);
        $kitItem->setSortOrder(5);
        $kitItemProduct = new Product();
        $kitItemProduct->setSku('KIT-SKU-1');
        $kitItemProductUnit = (new ProductUnit())->setCode('set');

        $kitItemReference = new ProductKitItem();
        ReflectionUtil::setId($kitItemReference, 11);
        $kitItemReference->setSortOrder(9);

        $sourceKitItemLineItem = new ProductKitItemLineItem();
        $sourceKitItemLineItem->setKitItem($kitItem);
        $sourceKitItemLineItem->setProduct($kitItemProduct);
        $sourceKitItemLineItem->setQuantity(3.0);
        $sourceKitItemLineItem->setUnit($kitItemProductUnit);
        $sourceKitItemLineItem->setSortOrder(7);

        $product = new Product();
        $product->setSku('SKU-1');
        $productUnit = (new ProductUnit())->setCode('each');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->addKitItemLineItem($sourceKitItemLineItem);

        $draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);
        $draftSyncReferenceResolver->expects(self::exactly(5))
            ->method('getReference')
            ->willReturnMap([
                [$product, $product],
                [$productUnit, $productUnit],
                [$kitItem, $kitItemReference],
                [$kitItemProduct, $kitItemProduct],
                [$kitItemProductUnit, $kitItemProductUnit],
            ]);

        $factory = new OrderLineItemDraftFromShoppingListFactory($draftSyncReferenceResolver);

        $orderLineItemDraft = $factory->createDraft($lineItem, 'draft-session-uuid');

        /** @var OrderProductKitItemLineItem $targetKitItemLineItem */
        $targetKitItemLineItem = $orderLineItemDraft->getKitItemLineItems()->first();

        self::assertSame($kitItemReference, $targetKitItemLineItem->getKitItem());
        self::assertSame(7, $targetKitItemLineItem->getSortOrder());
    }

    public function testCreateDraftUsesResolvedReferencesForKitItemLineItems(): void
    {
        $kitItem = new ProductKitItem();
        ReflectionUtil::setId($kitItem, 10);
        $kitItemProduct = new Product();
        $kitItemProduct->setSku('KIT-SKU-1');
        $kitItemProductUnit = (new ProductUnit())->setCode('set');

        $kitItemReference = new ProductKitItem();
        ReflectionUtil::setId($kitItemReference, 11);
        $kitItemProductReference = new Product();
        $kitItemProductReference->setSku('KIT-SKU-REFERENCE');
        $kitItemProductUnitReference = (new ProductUnit())->setCode('piece');

        $sourceKitItemLineItem = new ProductKitItemLineItem();
        $sourceKitItemLineItem->setKitItem($kitItem);
        $sourceKitItemLineItem->setProduct($kitItemProduct);
        $sourceKitItemLineItem->setQuantity(3.0);
        $sourceKitItemLineItem->setUnit($kitItemProductUnit);

        $product = new Product();
        $product->setSku('SKU-1');
        $productUnit = (new ProductUnit())->setCode('each');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->addKitItemLineItem($sourceKitItemLineItem);

        $draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);
        $draftSyncReferenceResolver->expects(self::exactly(5))
            ->method('getReference')
            ->willReturnMap([
                [$product, $product],
                [$productUnit, $productUnit],
                [$kitItem, $kitItemReference],
                [$kitItemProduct, $kitItemProductReference],
                [$kitItemProductUnit, $kitItemProductUnitReference],
            ]);

        $factory = new OrderLineItemDraftFromShoppingListFactory($draftSyncReferenceResolver);

        $orderLineItemDraft = $factory->createDraft($lineItem, 'draft-session-uuid');

        /** @var OrderProductKitItemLineItem $targetKitItemLineItem */
        $targetKitItemLineItem = $orderLineItemDraft->getKitItemLineItems()->first();
        self::assertSame($kitItemReference, $targetKitItemLineItem->getKitItem());
        self::assertSame($kitItemProductReference, $targetKitItemLineItem->getProduct());
        self::assertSame($kitItemProductUnitReference, $targetKitItemLineItem->getProductUnit());
    }

    public function testCreateDraftWhenKitItemLineItemQuantityIsNull(): void
    {
        $kitItem = new ProductKitItem();
        ReflectionUtil::setId($kitItem, 10);
        $kitItemProduct = new Product();
        $kitItemProduct->setSku('KIT-SKU-1');

        // Unlike the line item quantity, the kit item line item quantity is copied as is, without a fallback.
        $sourceKitItemLineItem = new ProductKitItemLineItem();
        $sourceKitItemLineItem->setKitItem($kitItem);
        $sourceKitItemLineItem->setProduct($kitItemProduct);
        $sourceKitItemLineItem->setQuantity(null);
        $sourceKitItemLineItem->setUnit((new ProductUnit())->setCode('set'));

        $product = new Product();
        $product->setSku('SKU-1');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit((new ProductUnit())->setCode('each'));
        $lineItem->addKitItemLineItem($sourceKitItemLineItem);

        $orderLineItemDraft = $this->factory->createDraft($lineItem, 'draft-session-uuid');

        /** @var OrderProductKitItemLineItem $targetKitItemLineItem */
        $targetKitItemLineItem = $orderLineItemDraft->getKitItemLineItems()->first();
        self::assertNull($targetKitItemLineItem->getQuantity());
    }

    public function testCreateDraftWithEmptyKitItemLineItems(): void
    {
        $product = new Product();
        $product->setSku('SKU-1');

        $lineItem = new LineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit((new ProductUnit())->setCode('each'));

        $orderLineItemDraft = $this->factory->createDraft($lineItem, 'draft-session-uuid');

        self::assertCount(0, $orderLineItemDraft->getKitItemLineItems());
    }
}
