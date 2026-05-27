<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Tests\Unit\DraftSession\Factory;

use Oro\Bundle\EntityExtendBundle\Test\ExtendedEntityTestTrait;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;
use Oro\Bundle\RFPBundle\DraftSession\Factory\OrderLineItemDraftFromRfqFactory;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemDraftFromRfqFactoryTest extends TestCase
{
    use ExtendedEntityTestTrait;

    private DefaultProductUnitProviderInterface&MockObject $defaultProductUnitProvider;

    private EntityDraftSyncReferenceResolver&MockObject $draftSyncReferenceResolver;

    private OrderLineItemDraftFromRfqFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->draftSyncReferenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);
        $this->draftSyncReferenceResolver
            ->method('getReference')
            ->willReturnCallback(static fn (?object $entity): ?object => $entity);
        $this->defaultProductUnitProvider = $this->createMock(DefaultProductUnitProviderInterface::class);

        $this->factory = new OrderLineItemDraftFromRfqFactory(
            $this->draftSyncReferenceResolver,
            $this->defaultProductUnitProvider,
        );
    }

    public function testSupportsReturnsTrueForRequestProduct(): void
    {
        self::assertTrue($this->factory->supports(RequestProduct::class));
    }

    public function testSupportsReturnsFalseForOtherClass(): void
    {
        self::assertFalse($this->factory->supports(OrderLineItem::class));
    }

    public function testCreateDraftSetsDraftSessionUuid(): void
    {
        $draftSessionUuid = 'test-uuid-line-item';

        $requestProductValues = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $requestProduct = new RequestProduct();
        $result = $this->factory->createDraft($requestProduct, $draftSessionUuid);

        self::assertSame($draftSessionUuid, $result->getDraftSessionUuid());
    }

    public function testCreateDraftSynchronizesFields(): void
    {
        $draftSessionUuid = 'uuid-sync-fields';

        $productUnit = (new ProductUnit())->setCode('item');
        $unitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product = new Product();
        $product->setPrimaryUnitPrecision($unitPrecision);

        $requestProduct = new RequestProduct();
        $requestProduct->setProduct($product);
        $requestProduct->setProductSku('SKU-001');
        $requestProduct->setComment('A test comment');

        $requestProductValues = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'getRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $result = $requestProductValues[spl_object_id($object)] ?? null;

                return true;
            }
        );

        $result = $this->factory->createDraft($requestProduct, $draftSessionUuid);

        self::assertSame($product, $result->getProduct());
        self::assertSame('SKU-001', $result->getProductSku());
        self::assertSame($productUnit, $result->getProductUnit());
        self::assertSame('item', $result->getProductUnitCode());
        self::assertSame(1, $result->getQuantity());
        self::assertSame('A test comment', $result->getComment());
        self::assertSame($requestProduct, $result->getRequestProduct());
    }

    public function testCreateDraftDoesNotSetProductUnitWhenProductHasNoPrimaryUnitPrecision(): void
    {
        $draftSessionUuid = 'uuid-no-primary-precision';

        $product = new Product();

        $requestProduct = new RequestProduct();
        $requestProduct->setProduct($product);
        $requestProduct->setProductSku('SKU-NO-UNIT');

        $this->defaultProductUnitProvider
            ->expects(self::never())
            ->method('getDefaultProductUnitPrecision');

        $requestProductValues = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $result = $this->factory->createDraft($requestProduct, $draftSessionUuid);

        self::assertSame($product, $result->getProduct());
        self::assertSame('SKU-NO-UNIT', $result->getProductSku());
        self::assertNull($result->getProductUnit());
        self::assertNull($result->getProductUnitCode());
    }

    public function testCreateDraftCreatesFreeFormItemWhenProductIsNull(): void
    {
        $draftSessionUuid = 'uuid-null-product';

        $rfqUnit = (new ProductUnit())->setCode('piece');

        $this->defaultProductUnitProvider
            ->expects(self::never())
            ->method('getDefaultProductUnitPrecision');

        $requestProduct = new RequestProduct();
        $requestProduct->setProductSku('DELETED-SKU');

        $requestProductItem = new RequestProductItem();
        $requestProductItem->setProductUnit($rfqUnit);
        $requestProduct->addRequestProductItem($requestProductItem);

        $requestProductValues = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $result = $this->factory->createDraft($requestProduct, $draftSessionUuid);

        self::assertNull($result->getProduct());
        self::assertSame('DELETED-SKU', $result->getFreeFormProduct());
        self::assertSame('DELETED-SKU', $result->getProductSku());
        self::assertSame($rfqUnit, $result->getProductUnit());
        self::assertSame('piece', $result->getProductUnitCode());
        self::assertSame(1, $result->getQuantity());
        self::assertTrue($result->isFreeForm());
    }

    public function testCreateDraftCreatesFreeFormItemFallsBackToDefaultUnit(): void
    {
        $draftSessionUuid = 'uuid-null-product-default-unit';

        $defaultUnit = (new ProductUnit())->setCode('each');
        $defaultUnitPrecision = (new ProductUnitPrecision())->setUnit($defaultUnit);

        $this->defaultProductUnitProvider
            ->expects(self::once())
            ->method('getDefaultProductUnitPrecision')
            ->willReturn($defaultUnitPrecision);

        $requestProduct = new RequestProduct();
        $requestProduct->setProductSku('DELETED-SKU');

        $requestProductValues = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $result = $this->factory->createDraft($requestProduct, $draftSessionUuid);

        self::assertNull($result->getProduct());
        self::assertSame('DELETED-SKU', $result->getFreeFormProduct());
        self::assertSame($defaultUnit, $result->getProductUnit());
        self::assertSame('each', $result->getProductUnitCode());
        self::assertTrue($result->isFreeForm());
    }

    public function testCreateDraftCreatesFreeFormItemWithNullFallbackUnit(): void
    {
        $draftSessionUuid = 'uuid-null-product-null-unit';

        $this->defaultProductUnitProvider
            ->expects(self::once())
            ->method('getDefaultProductUnitPrecision')
            ->willReturn(null);

        $requestProduct = new RequestProduct();
        $requestProduct->setProductSku('DELETED-SKU-2');

        $requestProductValues = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $result = $this->factory->createDraft($requestProduct, $draftSessionUuid);

        self::assertNull($result->getProduct());
        self::assertSame('DELETED-SKU-2', $result->getFreeFormProduct());
        self::assertNull($result->getProductUnit());
        self::assertTrue($result->isFreeForm());
    }

    public function testCreateDraftDoesNotSetFreeFormProductWhenSkuIsEmpty(): void
    {
        $draftSessionUuid = 'uuid-null-product-empty-sku';

        $this->defaultProductUnitProvider
            ->expects(self::once())
            ->method('getDefaultProductUnitPrecision')
            ->willReturn(null);

        $requestProduct = new RequestProduct();

        $requestProductValues = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $result = $this->factory->createDraft($requestProduct, $draftSessionUuid);

        self::assertNull($result->getProduct());
        self::assertNull($result->getFreeFormProduct());
        self::assertNull($result->getProductUnit());
        self::assertFalse($result->isFreeForm());
    }

    public function testCreateDraftSetsDraftSessionUuidOnKitItemLineItems(): void
    {
        $draftSessionUuid = 'uuid-kit-item-draft-session';

        $product = new Product();
        $productUnit = (new ProductUnit())->setCode('each');
        $unitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product->setPrimaryUnitPrecision($unitPrecision);

        $kitItem = new ProductKitItem();
        ReflectionUtil::setId($kitItem, 42);

        $this->entityFieldTestExtension->addExpectation(
            ProductKitItem::class,
            'getDefaultLabel',
            static function (array $arguments, object $object, mixed &$result): bool {
                $result = null;

                return true;
            }
        );

        $sourceKitItem = new RequestProductKitItemLineItem();
        $sourceKitItem->setKitItemId(42);
        $sourceKitItem->setKitItem($kitItem);
        $sourceKitItem->setQuantity(1.0);

        $requestProduct = new RequestProduct();
        $requestProduct->setProduct($product);
        $requestProduct->addKitItemLineItem($sourceKitItem);

        $requestProductValues = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $result = $this->factory->createDraft($requestProduct, $draftSessionUuid);

        $kitItemLineItems = $result->getKitItemLineItems();
        self::assertCount(1, $kitItemLineItems);

        /** @var OrderProductKitItemLineItem $targetKitItem */
        $targetKitItem = $kitItemLineItems->first();
        self::assertSame($draftSessionUuid, $targetKitItem->getDraftSessionUuid());
    }

    public function testCreateDraftSynchronizesKitItemLineItems(): void
    {
        $draftSessionUuid = 'uuid-kit-items';

        $product = new Product();
        $productUnit = (new ProductUnit())->setCode('each');
        $unitPrecision = (new ProductUnitPrecision())->setUnit($productUnit);
        $product->setPrimaryUnitPrecision($unitPrecision);

        $kitItemProduct = new Product();
        $kitItemProductUnit = (new ProductUnit())->setCode('set');
        $kitItem = new ProductKitItem();
        ReflectionUtil::setId($kitItem, 10);

        // ProductKitItem::getDefaultLabel() is an extended method called by updateKitItemFallbackFields()
        // when setKitItem() is invoked on the target line item. Stub it to return null.
        $this->entityFieldTestExtension->addExpectation(
            ProductKitItem::class,
            'getDefaultLabel',
            static function (array $arguments, object $object, mixed &$result): bool {
                $result = null;

                return true;
            }
        );

        $sourceKitItem = new RequestProductKitItemLineItem();
        // setKitItemId must be set before setKitItem to avoid triggering updateKitItemFallbackFields
        // (the fallback sync is skipped when kitItemId matches kitItem->getId()).
        $sourceKitItem->setKitItemId(10);
        $sourceKitItem->setKitItem($kitItem);
        $sourceKitItem->setKitItemLabel('Kit Label');
        $sourceKitItem->setOptional(true);
        $sourceKitItem->setMinimumQuantity(1.0);
        $sourceKitItem->setMaximumQuantity(5.0);
        $sourceKitItem->setSortOrder(2);
        $sourceKitItem->setProduct($kitItemProduct);
        $sourceKitItem->setProductId(99);
        $sourceKitItem->setProductSku('KIT-SKU');
        $sourceKitItem->setProductName('Kit Product Name');
        $sourceKitItem->setQuantity(2.0);
        $sourceKitItem->setProductUnit($kitItemProductUnit);
        $sourceKitItem->setProductUnitCode('set');
        $sourceKitItem->setProductUnitPrecision(0);

        $requestProduct = new RequestProduct();
        $requestProduct->setProduct($product);
        $requestProduct->addKitItemLineItem($sourceKitItem);

        $requestProductValues = [];
        $this->entityFieldTestExtension->addExpectation(
            OrderLineItem::class,
            'setRequestProduct',
            static function (array $arguments, object $object, mixed &$result) use (&$requestProductValues): bool {
                $requestProductValues[spl_object_id($object)] = $arguments[0];
                $result = $object;

                return true;
            }
        );

        $result = $this->factory->createDraft($requestProduct, $draftSessionUuid);

        $kitItemLineItems = $result->getKitItemLineItems();
        self::assertCount(1, $kitItemLineItems);

        /** @var OrderProductKitItemLineItem $targetKitItem */
        $targetKitItem = $kitItemLineItems->first();
        self::assertSame($kitItem, $targetKitItem->getKitItem());
        self::assertSame(10, $targetKitItem->getKitItemId());
        self::assertSame('Kit Label', $targetKitItem->getKitItemLabel());
        self::assertTrue($targetKitItem->isOptional());
        self::assertSame(1.0, $targetKitItem->getMinimumQuantity());
        self::assertSame(5.0, $targetKitItem->getMaximumQuantity());
        self::assertSame(2, $targetKitItem->getSortOrder());
        self::assertSame($kitItemProduct, $targetKitItem->getProduct());
        self::assertSame(99, $targetKitItem->getProductId());
        self::assertSame('KIT-SKU', $targetKitItem->getProductSku());
        self::assertSame('Kit Product Name', $targetKitItem->getProductName());
        self::assertSame(2.0, $targetKitItem->getQuantity());
        self::assertSame($kitItemProductUnit, $targetKitItem->getProductUnit());
        self::assertSame('set', $targetKitItem->getProductUnitCode());
        self::assertSame(0, $targetKitItem->getProductUnitPrecision());
    }
}
