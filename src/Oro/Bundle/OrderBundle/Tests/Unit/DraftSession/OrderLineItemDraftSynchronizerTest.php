<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\DraftSession\OrderLineItemDraftSynchronizer;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldsProvider;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldSynchronizer;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDraftSynchronizerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $entityManager;
    private EntityDraftExtendedFieldsProvider&MockObject $extendedFieldsProvider;
    private EntityDraftExtendedFieldSynchronizer&MockObject $extendedFieldSynchronizer;
    private OrderLineItemDraftSynchronizer $synchronizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->extendedFieldsProvider = $this->createMock(EntityDraftExtendedFieldsProvider::class);
        $this->extendedFieldSynchronizer = $this->createMock(EntityDraftExtendedFieldSynchronizer::class);

        $referenceResolver = new EntityDraftSyncReferenceResolver($this->doctrine);

        $this->synchronizer = new OrderLineItemDraftSynchronizer(
            $referenceResolver,
            $this->extendedFieldsProvider,
            $this->extendedFieldSynchronizer,
        );
    }

    public function testSupportsOrderLineItem(): void
    {
        self::assertTrue($this->synchronizer->supports(OrderLineItem::class));
    }

    public function testDoesNotSupportOtherClasses(): void
    {
        self::assertFalse($this->synchronizer->supports(\stdClass::class));
    }

    public function testSynchronizeFromDraftCopiesAllFields(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 100);

        $parentProduct = new Product();
        ReflectionUtil::setId($parentProduct, 200);

        $productUnit = (new ProductUnit())->setCode('kg');

        $price = Price::create(250.75, 'EUR');
        $shipBy = new \DateTime('2026-12-25');

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 2000);
        $lineItemDraft->setProduct($product);
        $lineItemDraft->setParentProduct($parentProduct);
        $lineItemDraft->setProductSku('PROD-SKU-999');
        $lineItemDraft->setProductName('Premium Product');
        $lineItemDraft->setProductVariantFields(['size', 'color']);
        $lineItemDraft->setFreeFormProduct('Custom Item');
        $lineItemDraft->setQuantity(10.5);
        $lineItemDraft->setProductUnit($productUnit);
        $lineItemDraft->setProductUnitCode('kg');
        $lineItemDraft->setPrice($price);
        $lineItemDraft->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_UNIT);
        $lineItemDraft->setShipBy($shipBy);
        $lineItemDraft->setFromExternalSource(true);
        $lineItemDraft->setComment('Special handling required');
        $lineItemDraft->setShippingMethod('express');
        $lineItemDraft->setShippingMethodType('overnight');
        $lineItemDraft->setShippingEstimateAmount(45.99);
        $lineItemDraft->setChecksum('xyz789');

        $lineItem = new OrderLineItem();

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $this->synchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);

        self::assertSame($product, $lineItem->getProduct());
        self::assertSame($parentProduct, $lineItem->getParentProduct());
        self::assertEquals('PROD-SKU-999', $lineItem->getProductSku());
        self::assertEquals('Premium Product', $lineItem->getProductName());
        self::assertEquals(['size', 'color'], $lineItem->getProductVariantFields());
        self::assertEquals('Custom Item', $lineItem->getFreeFormProduct());
        self::assertEquals(10.5, $lineItem->getQuantity());
        self::assertSame($productUnit, $lineItem->getProductUnit());
        self::assertEquals('kg', $lineItem->getProductUnitCode());
        self::assertNotNull($lineItem->getPrice());
        self::assertNotSame($price, $lineItem->getPrice());
        self::assertEquals(250.75, $lineItem->getPrice()->getValue());
        self::assertEquals('EUR', $lineItem->getPrice()->getCurrency());
        self::assertEquals(PriceTypeAwareInterface::PRICE_TYPE_UNIT, $lineItem->getPriceType());
        self::assertNotNull($lineItem->getShipBy());
        self::assertNotSame($shipBy, $lineItem->getShipBy());
        self::assertTrue($lineItem->isFromExternalSource());
        self::assertEquals('Special handling required', $lineItem->getComment());
        self::assertEquals('express', $lineItem->getShippingMethod());
        self::assertEquals('overnight', $lineItem->getShippingMethodType());
        self::assertEquals(45.99, $lineItem->getShippingEstimateAmount());
        self::assertEquals('xyz789', $lineItem->getChecksum());
    }

    public function testSynchronizeFromDraftAddsToDraftCollectionWhenLineItemHasNoId(): void
    {
        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 3600);

        $lineItem = new OrderLineItem();

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        self::assertNull($lineItem->getId());

        $this->synchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);

        self::assertTrue($lineItem->getDrafts()->contains($lineItemDraft));
    }

    public function testSynchronizeFromDraftDoesNotAddToDraftCollectionWhenLineItemHasId(): void
    {
        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 3600);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 3700);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $this->synchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);

        self::assertCount(0, $lineItem->getDrafts());
    }

    public function testSynchronizeToDraftSetsDraftSource(): void
    {
        $draftSource = new OrderLineItem();
        ReflectionUtil::setId($draftSource, 500);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 5100);

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 5200);
        $lineItemDraft->setDraftSource($draftSource);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $this->synchronizer->synchronizeToDraft($lineItem, $lineItemDraft);

        self::assertSame($draftSource, $lineItemDraft->getDraftSource());
    }

    public function testSynchronizeFromDraftClearsNullPrice(): void
    {
        $lineItemDraft = new OrderLineItem();
        // price is null by default

        $lineItem = new OrderLineItem();
        $lineItem->setPrice(Price::create(100.00, 'USD'));

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $this->synchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);

        self::assertNull($lineItem->getPrice());
    }

    public function testSynchronizeFromDraftClearsNullShipBy(): void
    {
        $lineItemDraft = new OrderLineItem();
        // shipBy is null by default

        $lineItem = new OrderLineItem();
        $lineItem->setShipBy(new \DateTime('2026-05-01'));

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $this->synchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);

        self::assertNull($lineItem->getShipBy());
    }

    public function testSynchronizeFromDraftUpdatesExistingKitItemLineItem(): void
    {
        $kitItem = new ProductKitItemStub(50);

        $sourceProduct = new Product();
        ReflectionUtil::setId($sourceProduct, 300);

        $sourceProductUnit = (new ProductUnit())->setCode('set');
        $sourcePrice = Price::create(199.99, 'USD');

        $sourceKitItemLineItem = new OrderProductKitItemLineItem();
        $sourceKitItemLineItem->setKitItem($kitItem);
        $sourceKitItemLineItem->setProduct($sourceProduct);
        $sourceKitItemLineItem->setQuantity(3.0);
        $sourceKitItemLineItem->setProductUnit($sourceProductUnit);
        $sourceKitItemLineItem->setSortOrder(20);
        $sourceKitItemLineItem->setPrice($sourcePrice);

        $targetProduct = new Product();
        ReflectionUtil::setId($targetProduct, 400);

        $targetKitItemLineItem = new OrderProductKitItemLineItem();
        $targetKitItemLineItem->setKitItem($kitItem);
        $targetKitItemLineItem->setProduct($targetProduct);
        $targetKitItemLineItem->setQuantity(1.0);
        $targetKitItemLineItem->setSortOrder(5);

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 6000);
        $lineItemDraft->addKitItemLineItem($sourceKitItemLineItem);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 6100);
        $lineItem->addKitItemLineItem($targetKitItemLineItem);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);
        $this->extendedFieldsProvider->expects(self::once())
            ->method('getApplicableExtendedFields')
            ->with(OrderProductKitItemLineItem::class)
            ->willReturn([]);

        $this->synchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);

        self::assertCount(1, $lineItem->getKitItemLineItems());
        $updatedKitItem = $lineItem->getKitItemLineItems()->get(50);
        self::assertSame($targetKitItemLineItem, $updatedKitItem);
        self::assertSame($sourceProduct, $updatedKitItem->getProduct());
        self::assertEquals(3.0, $updatedKitItem->getQuantity());
        self::assertSame($sourceProductUnit, $updatedKitItem->getProductUnit());
        self::assertEquals(20, $updatedKitItem->getSortOrder());
        self::assertNotNull($updatedKitItem->getPrice());
        self::assertNotSame($sourcePrice, $updatedKitItem->getPrice());
        self::assertEquals(199.99, $updatedKitItem->getPrice()->getValue());
    }

    public function testSynchronizeFromDraftRemovesKitItemLineItemNotInSource(): void
    {
        $kitItemInSource = new ProductKitItemStub(50);
        $kitItemOnlyInTarget = new ProductKitItemStub(60);

        $sourceKitItemLineItem = new OrderProductKitItemLineItem();
        $sourceKitItemLineItem->setKitItem($kitItemInSource);
        $sourceKitItemLineItem->setQuantity(1.0);

        $targetKitItemLineItemToRemove = new OrderProductKitItemLineItem();
        $targetKitItemLineItemToRemove->setKitItem($kitItemOnlyInTarget);
        $targetKitItemLineItemToRemove->setQuantity(2.0);

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 7000);
        $lineItemDraft->addKitItemLineItem($sourceKitItemLineItem);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 7100);
        $lineItem->addKitItemLineItem($targetKitItemLineItemToRemove);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);
        $this->extendedFieldsProvider->expects(self::once())
            ->method('getApplicableExtendedFields')
            ->with(OrderProductKitItemLineItem::class)
            ->willReturn([]);

        self::assertCount(1, $lineItem->getKitItemLineItems());
        self::assertTrue($lineItem->getKitItemLineItems()->containsKey(60));

        $this->synchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);

        self::assertCount(1, $lineItem->getKitItemLineItems());
        self::assertFalse($lineItem->getKitItemLineItems()->containsKey(60));
        self::assertTrue($lineItem->getKitItemLineItems()->containsKey(50));
    }

    public function testSynchronizeToDraftCopiesAllFields(): void
    {
        $product = new Product();
        ReflectionUtil::setId($product, 800);

        $parentProduct = new Product();
        ReflectionUtil::setId($parentProduct, 900);

        $productUnit = (new ProductUnit())->setCode('item');
        $price = Price::create(55.00, 'USD');
        $shipBy = new \DateTime('2026-11-01');

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 8000);
        $lineItem->setProduct($product);
        $lineItem->setParentProduct($parentProduct);
        $lineItem->setProductSku('SKU-DRAFT-SYNC');
        $lineItem->setProductName('Sync Product');
        $lineItem->setProductVariantFields(['color' => 'red']);
        $lineItem->setFreeFormProduct('Free Form');
        $lineItem->setQuantity(7.0);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setProductUnitCode('item');
        $lineItem->setPrice($price);
        $lineItem->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_BUNDLED);
        $lineItem->setShipBy($shipBy);
        $lineItem->setFromExternalSource(true);
        $lineItem->setComment('Sync to draft');
        $lineItem->setShippingMethod('dhl');
        $lineItem->setShippingMethodType('standard');
        $lineItem->setShippingEstimateAmount(12.50);
        $lineItem->setChecksum('checksum-abc');

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 8100);
        $lineItemDraft->setDraftSource($lineItem);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);

        $this->synchronizer->synchronizeToDraft($lineItem, $lineItemDraft);

        self::assertSame($product, $lineItemDraft->getProduct());
        self::assertSame($parentProduct, $lineItemDraft->getParentProduct());
        self::assertEquals('SKU-DRAFT-SYNC', $lineItemDraft->getProductSku());
        self::assertEquals('Sync Product', $lineItemDraft->getProductName());
        self::assertEquals(['color' => 'red'], $lineItemDraft->getProductVariantFields());
        self::assertEquals('Free Form', $lineItemDraft->getFreeFormProduct());
        self::assertEquals(7.0, $lineItemDraft->getQuantity());
        self::assertSame($productUnit, $lineItemDraft->getProductUnit());
        self::assertEquals('item', $lineItemDraft->getProductUnitCode());
        self::assertNotNull($lineItemDraft->getPrice());
        self::assertNotSame($price, $lineItemDraft->getPrice());
        self::assertEquals(55.00, $lineItemDraft->getPrice()->getValue());
        self::assertEquals('USD', $lineItemDraft->getPrice()->getCurrency());
        self::assertEquals(PriceTypeAwareInterface::PRICE_TYPE_BUNDLED, $lineItemDraft->getPriceType());
        self::assertNotNull($lineItemDraft->getShipBy());
        self::assertNotSame($shipBy, $lineItemDraft->getShipBy());
        self::assertEquals($shipBy, $lineItemDraft->getShipBy());
        self::assertTrue($lineItemDraft->isFromExternalSource());
        self::assertEquals('Sync to draft', $lineItemDraft->getComment());
        self::assertEquals('dhl', $lineItemDraft->getShippingMethod());
        self::assertEquals('standard', $lineItemDraft->getShippingMethodType());
        self::assertEquals(12.50, $lineItemDraft->getShippingEstimateAmount());
        self::assertEquals('checksum-abc', $lineItemDraft->getChecksum());
        self::assertSame($lineItem, $lineItemDraft->getDraftSource());
    }

    public function testSynchronizeToDraftSyncsKitItemLineItems(): void
    {
        $kitItem = new ProductKitItemStub(50);

        $product = new Product();
        ReflectionUtil::setId($product, 150);

        $productUnit = (new ProductUnit())->setCode('box');
        $price = Price::create(75.00, 'USD');

        $sourceKitItemLineItem = new OrderProductKitItemLineItem();
        $sourceKitItemLineItem->setKitItem($kitItem);
        $sourceKitItemLineItem->setKitItemLabel('Kit Label');
        $sourceKitItemLineItem->setOptional(true);
        $sourceKitItemLineItem->setMinimumQuantity(1.0);
        $sourceKitItemLineItem->setMaximumQuantity(5.0);
        $sourceKitItemLineItem->setSortOrder(15);
        $sourceKitItemLineItem->setProduct($product);
        $sourceKitItemLineItem->setProductSku('KIT-SKU');
        $sourceKitItemLineItem->setQuantity(3.0);
        $sourceKitItemLineItem->setProductUnit($productUnit);
        $sourceKitItemLineItem->setProductUnitCode('box');
        $sourceKitItemLineItem->setProductUnitPrecision(0);
        $sourceKitItemLineItem->setPrice($price);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 8500);
        $lineItem->addKitItemLineItem($sourceKitItemLineItem);

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 8600);
        $lineItemDraft->setDraftSource($lineItem);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);
        $this->extendedFieldsProvider->expects(self::once())
            ->method('getApplicableExtendedFields')
            ->with(OrderProductKitItemLineItem::class)
            ->willReturn([]);

        $this->synchronizer->synchronizeToDraft($lineItem, $lineItemDraft);

        self::assertCount(1, $lineItemDraft->getKitItemLineItems());
        $syncedKitItem = $lineItemDraft->getKitItemLineItems()->first();

        self::assertSame($kitItem, $syncedKitItem->getKitItem());
        self::assertEquals(50, $syncedKitItem->getKitItemId());
        self::assertEquals('Kit Label', $syncedKitItem->getKitItemLabel());
        self::assertTrue($syncedKitItem->isOptional());
        self::assertEquals(1.0, $syncedKitItem->getMinimumQuantity());
        self::assertEquals(5.0, $syncedKitItem->getMaximumQuantity());
        self::assertEquals(15, $syncedKitItem->getSortOrder());
        self::assertSame($product, $syncedKitItem->getProduct());
        self::assertEquals(150, $syncedKitItem->getProductId());
        self::assertEquals('KIT-SKU', $syncedKitItem->getProductSku());
        self::assertEquals('KIT-SKU', $syncedKitItem->getProductName());
        self::assertEquals(3.0, $syncedKitItem->getQuantity());
        self::assertSame($productUnit, $syncedKitItem->getProductUnit());
        self::assertEquals('box', $syncedKitItem->getProductUnitCode());
        self::assertEquals(0, $syncedKitItem->getProductUnitPrecision());
        self::assertNotNull($syncedKitItem->getPrice());
        self::assertNotSame($price, $syncedKitItem->getPrice());
        self::assertEquals(75.00, $syncedKitItem->getPrice()->getValue());
        self::assertEquals('USD', $syncedKitItem->getPrice()->getCurrency());
    }

    public function testSynchronizeFromDraftSyncsKitItemLineItems(): void
    {
        $kitItem = new ProductKitItemStub(50);

        $product = new Product();
        ReflectionUtil::setId($product, 150);

        $productUnit = (new ProductUnit())->setCode('box');
        $price = Price::create(99.99, 'EUR');

        $sourceKitItemLineItem = new OrderProductKitItemLineItem();
        $sourceKitItemLineItem->setKitItem($kitItem);
        $sourceKitItemLineItem->setKitItemLabel('Kit Item Label');
        $sourceKitItemLineItem->setOptional(true);
        $sourceKitItemLineItem->setMinimumQuantity(1.0);
        $sourceKitItemLineItem->setMaximumQuantity(10.0);
        $sourceKitItemLineItem->setSortOrder(10);
        $sourceKitItemLineItem->setProduct($product);
        $sourceKitItemLineItem->setProductSku('KIT-PROD-SKU');
        $sourceKitItemLineItem->setQuantity(5.5);
        $sourceKitItemLineItem->setProductUnit($productUnit);
        $sourceKitItemLineItem->setProductUnitCode('box');
        $sourceKitItemLineItem->setProductUnitPrecision(2);
        $sourceKitItemLineItem->setPrice($price);

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 3200);
        $lineItemDraft->addKitItemLineItem($sourceKitItemLineItem);

        $lineItem = new OrderLineItem();

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);
        $this->extendedFieldsProvider->expects(self::once())
            ->method('getApplicableExtendedFields')
            ->with(OrderProductKitItemLineItem::class)
            ->willReturn([]);

        $this->synchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);

        self::assertCount(1, $lineItem->getKitItemLineItems());
        $syncedKitItem = $lineItem->getKitItemLineItems()->first();

        self::assertSame($kitItem, $syncedKitItem->getKitItem());
        self::assertEquals(50, $syncedKitItem->getKitItemId());
        self::assertEquals('Kit Item Label', $syncedKitItem->getKitItemLabel());
        self::assertTrue($syncedKitItem->isOptional());
        self::assertEquals(1.0, $syncedKitItem->getMinimumQuantity());
        self::assertEquals(10.0, $syncedKitItem->getMaximumQuantity());
        self::assertEquals(10, $syncedKitItem->getSortOrder());
        self::assertSame($product, $syncedKitItem->getProduct());
        self::assertEquals(150, $syncedKitItem->getProductId());
        self::assertEquals('KIT-PROD-SKU', $syncedKitItem->getProductSku());
        self::assertEquals('KIT-PROD-SKU', $syncedKitItem->getProductName());
        self::assertEquals(5.5, $syncedKitItem->getQuantity());
        self::assertSame($productUnit, $syncedKitItem->getProductUnit());
        self::assertEquals('box', $syncedKitItem->getProductUnitCode());
        self::assertEquals(2, $syncedKitItem->getProductUnitPrecision());
        self::assertNotNull($syncedKitItem->getPrice());
        self::assertNotSame($price, $syncedKitItem->getPrice());
        self::assertEquals(99.99, $syncedKitItem->getPrice()->getValue());
        self::assertEquals('EUR', $syncedKitItem->getPrice()->getCurrency());
    }

    public function testSynchronizeFromDraftSyncsKitItemLineItemExtendedFields(): void
    {
        $kitItem = new ProductKitItemStub(50);

        $sourceKitItemLineItem = new OrderProductKitItemLineItem();
        $sourceKitItemLineItem->setKitItem($kitItem);
        $sourceKitItemLineItem->setQuantity(2.0);

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 9000);
        $lineItemDraft->addKitItemLineItem($sourceKitItemLineItem);

        $lineItem = new OrderLineItem();

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);
        $this->extendedFieldsProvider->expects(self::once())
            ->method('getApplicableExtendedFields')
            ->with(OrderProductKitItemLineItem::class)
            ->willReturn(['custom_field' => 'string', 'custom_enum' => 'enum']);

        $synchronizedFields = [];
        $this->extendedFieldSynchronizer->expects(self::exactly(2))
            ->method('synchronize')
            ->willReturnCallback(
                function ($source, $target, $fieldName, $fieldType) use (&$synchronizedFields) {
                    self::assertInstanceOf(OrderProductKitItemLineItem::class, $source);
                    self::assertInstanceOf(OrderProductKitItemLineItem::class, $target);
                    $synchronizedFields[$fieldName] = $fieldType;
                }
            );

        $this->synchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);

        self::assertCount(1, $lineItem->getKitItemLineItems());
        self::assertEquals(['custom_field' => 'string', 'custom_enum' => 'enum'], $synchronizedFields);
    }

    public function testSynchronizeFromDraftClearsNullPriceOnKitItemLineItem(): void
    {
        $kitItem = new ProductKitItemStub(50);

        $sourceKitItemLineItem = new OrderProductKitItemLineItem();
        $sourceKitItemLineItem->setKitItem($kitItem);
        $sourceKitItemLineItem->setQuantity(1.0);
        // price is null by default

        $targetKitItemLineItem = new OrderProductKitItemLineItem();
        $targetKitItemLineItem->setKitItem($kitItem);
        $targetKitItemLineItem->setQuantity(1.0);
        $targetKitItemLineItem->setPrice(Price::create(50.00, 'USD'));

        $lineItemDraft = new OrderLineItem();
        ReflectionUtil::setId($lineItemDraft, 9100);
        $lineItemDraft->addKitItemLineItem($sourceKitItemLineItem);

        $lineItem = new OrderLineItem();
        ReflectionUtil::setId($lineItem, 9200);
        $lineItem->addKitItemLineItem($targetKitItemLineItem);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->entityManager);
        $this->entityManager->expects(self::any())
            ->method('contains')
            ->willReturn(true);
        $this->extendedFieldsProvider->expects(self::once())
            ->method('getApplicableExtendedFields')
            ->with(OrderProductKitItemLineItem::class)
            ->willReturn([]);

        $this->synchronizer->synchronizeFromDraft($lineItemDraft, $lineItem);

        $syncedKitItem = $lineItem->getKitItemLineItems()->first();
        self::assertNull($syncedKitItem->getPrice());
    }
}
