<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrderLineItemTest extends TestCase
{
    use EntityTestCaseTrait;

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    public function testProperties(): void
    {
        $now = new \DateTime('now');
        $checksum = sha1('sample-line-item');
        $properties = [
            ['id', 123],
            ['product', new ProductStub()],
            ['parentProduct', new ProductStub()],
            ['productSku', '1234'],
            ['productName', 'name', ''],
            ['freeFormProduct', 'Services'],
            ['quantity', 42],
            ['productUnit', new ProductUnit()],
            ['productUnitCode', 'item'],
            ['value', 42.00],
            ['currency', 'USD'],
            ['price', Price::create(42, 'USD')],
            ['priceType', 10],
            ['shipBy', $now],
            ['fromExternalSource', true],
            ['comment', 'The answer is 42'],
            ['shippingMethod', 'shipping_method'],
            ['shippingMethodType', 'shipping_method_type'],
            ['shippingEstimateAmount', 10.00],
            ['checksum', $checksum],
            ['draftSessionUuid', '8f091a9a-c0d7-4560-975a-d3b0090bcfbd'],
            ['draftSource', new OrderLineItem()],
            ['draftDelete', true],
        ];

        $entity = new OrderLineItem();
        self::assertPropertyAccessors($entity, $properties);

        $productKitItem = new ProductKitItemStub(42);
        $orderProductKitItemLineItem = (new OrderProductKitItemLineItem())
            ->setKitItem($productKitItem);

        self::assertSame([], $entity->getKitItemLineItems()->toArray());

        $entity->addKitItemLineItem($orderProductKitItemLineItem);
        self::assertSame(
            [$productKitItem->getId() => $orderProductKitItemLineItem],
            $entity->getKitItemLineItems()->toArray()
        );

        $entity->removeKitItemLineItem($orderProductKitItemLineItem);
        self::assertSame([], $entity->getKitItemLineItems()->toArray());
        self::assertPropertyCollection($entity, 'orders', new Order());
        self::assertPropertyCollection($entity, 'drafts', new OrderLineItem());
    }

    public function testSetKitItemLineItems(): void
    {
        $entity = new OrderLineItem();
        $entity->addKitItemLineItem(new OrderProductKitItemLineItem());

        $oldCollection = $entity->getKitItemLineItems();

        $entity->setKitItemLineItems(new ArrayCollection());
        $newCollection = $entity->getKitItemLineItems();

        self::assertNotSame($newCollection, $oldCollection);
    }

    public function testCreatePrice(): void
    {
        $entity = new OrderLineItem();
        self::assertEmpty($entity->getPrice());
        $entity->setValue(42);
        $entity->setCurrency('USD');
        $entity->createPrice();
        self::assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testPriceNotInitializedWithValueWithoutCurrency(): void
    {
        $orderLineItem = new OrderLineItem();
        self::assertEmpty($orderLineItem->getPrice());
        $orderLineItem->setValue(42);
        self::assertEmpty($orderLineItem->getPrice());
    }

    public function testPriceNotInitializedWithCurrencyWithoutValue(): void
    {
        $orderLineItem = new OrderLineItem();
        self::assertEmpty($orderLineItem->getPrice());
        $orderLineItem->setCurrency('USD');
        self::assertEmpty($orderLineItem->getPrice());
    }

    public function testCreatePriceCalledOnSetCurrency(): void
    {
        $entity = new OrderLineItem();
        self::assertEmpty($entity->getPrice());
        $entity->setValue(42);
        self::assertEmpty($entity->getPrice());
        $entity->setCurrency('USD');
        self::assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testCreatePriceCalledOnSetValue(): void
    {
        $entity = new OrderLineItem();
        self::assertEmpty($entity->getPrice());
        $entity->setCurrency('USD');
        self::assertEmpty($entity->getPrice());
        $entity->setValue(42);
        self::assertEquals(Price::create(42, 'USD'), $entity->getPrice());
    }

    public function testPrePersist(): void
    {
        $entity = new OrderLineItem();
        $entity->setPrice(Price::create(42, 'USD'));
        self::assertEquals(42, $entity->getValue());
        self::assertEquals('USD', $entity->getCurrency());

        $entity->getPrice()->setValue(84);
        $entity->getPrice()->setCurrency('EUR');

        self::assertEmpty($entity->getProductSku());
        self::assertEmpty($entity->getProductUnitCode());

        $productName = new ProductName();
        $productName->setString('Product Test Name');

        $product = new ProductStub();
        $product->setSku('SKU')
            ->addName($productName)
            ->prePersist();

        $entity->setProduct($product);
        $entity->setProductUnit((new ProductUnit())->setCode('kg'));

        $entity->preSave();
        self::assertEquals(84, $entity->getValue());
        self::assertEquals('EUR', $entity->getCurrency());
        self::assertEquals('SKU', $entity->getProductSku());
        self::assertEquals('Product Test Name', $entity->getProductName());
        self::assertEquals('kg', $entity->getProductUnitCode());
    }

    /**
     * @dataProvider isRequirePriceRecalculationDataProvider
     */
    public function testIsRequirePriceRecalculation(
        OrderLineItem $entity,
        string $method,
        mixed $value,
        bool $expectedResult
    ): void {
        ReflectionUtil::setPropertyValue($entity, 'requirePriceRecalculation', false);
        self::assertFalse($entity->isRequirePriceRecalculation());

        $entity->$method($value);
        self::assertEquals($expectedResult, $entity->isRequirePriceRecalculation());
    }

    public function isRequirePriceRecalculationDataProvider(): array
    {
        $lineItemWithProduct = new OrderLineItem();
        $lineItemWithProduct->setProduct($this->getProduct(42));

        $lineItemWithProductUnit = new OrderLineItem();
        $lineItemWithProductUnit->setProductUnit($this->getProductUnit('kg'));

        $lineItemWithQuantity = new OrderLineItem();
        $lineItemWithQuantity->setQuantity(21);

        return [
            [
                new OrderLineItem(),
                'setProduct',
                new ProductStub(),
                true,
            ],
            [
                new OrderLineItem(),
                'setProductUnit',
                new ProductUnit(),
                true,
            ],
            [
                new OrderLineItem(),
                'setQuantity',
                1,
                true,
            ],
            [
                $lineItemWithProduct,
                'setProduct',
                $this->getProduct(21),
                true,
            ],
            [
                $lineItemWithProductUnit,
                'setProductUnit',
                $this->getProductUnit('item'),
                true,
            ],
            [
                $lineItemWithQuantity,
                'setQuantity',
                1,
                true,
            ],
        ];
    }

    public function testPrice(): void
    {
        $lineItem = new OrderLineItem();
        self::assertNull($lineItem->getPrice());
        self::assertNull($lineItem->getCurrency());
        self::assertNull($lineItem->getValue());

        $price = Price::create(12.3456, 'USD');
        $lineItem->setPrice($price);
        self::assertSame($price->getCurrency(), $lineItem->getCurrency());
        self::assertSame((float)$price->getValue(), $lineItem->getValue());

        $lineItem->setValue(34.5678);
        self::assertEquals(Price::create(34.5678, 'USD'), $lineItem->getPrice());

        $lineItem->setCurrency('EUR');
        self::assertEquals(Price::create(34.5678, 'EUR'), $lineItem->getPrice());
    }

    public function testPriceWhenInvalid(): void
    {
        $lineItem = new OrderLineItem();

        $price = Price::create('foobar', 'USD');
        $lineItem->setPrice($price);
        self::assertSame($price->getCurrency(), $lineItem->getCurrency());
        self::assertSame(0.0, $lineItem->getValue());
        self::assertSame($price, $lineItem->getPrice());
    }

    public function testShippingCost(): void
    {
        $lineItem = new OrderLineItem();
        $lineItem->setCurrency('USD');
        $lineItem->setShippingEstimateAmount(7.00);
        $shippingCost = $lineItem->getShippingCost();

        self::assertInstanceOf(Price::class, $shippingCost);
        self::assertEquals(7.00, $shippingCost->getValue());
        self::assertEquals('USD', $shippingCost->getCurrency());
    }

    public function testDraftRelations(): void
    {
        $lineItem = new OrderLineItem();
        $draftLineItem = new OrderLineItem();
        $order = new Order();

        // Test drafts collection
        self::assertEmpty($lineItem->getDrafts()->toArray());
        $lineItem->addDraft($draftLineItem);
        self::assertCount(1, $lineItem->getDrafts());
        self::assertSame($lineItem, $draftLineItem->getDraftSource());

        $lineItem->removeDraft($draftLineItem);
        self::assertEmpty($lineItem->getDrafts()->toArray());
        self::assertNull($draftLineItem->getDraftSource());
    }

    public function testGetOrderReturnsNullWhenNoOrders(): void
    {
        $lineItem = new OrderLineItem();
        self::assertNull($lineItem->getOrder());
    }

    public function testGetOrderReturnsFirstOrderWhenNoSubOrders(): void
    {
        $lineItem = new OrderLineItem();
        $order1 = new Order();
        $order2 = new Order();

        $lineItem->addOrder($order1);
        $lineItem->addOrder($order2);

        self::assertSame($order1, $lineItem->getOrder());
    }

    public function testGetOrderReturnsOrderWithSubOrders(): void
    {
        $lineItem = new OrderLineItem();
        $orderWithoutSubOrders = new Order();
        $orderWithSubOrders = new Order();
        $subOrder = new Order();

        $orderWithSubOrders->addSubOrder($subOrder);

        $lineItem->addOrder($orderWithoutSubOrders);
        $lineItem->addOrder($orderWithSubOrders);

        self::assertSame($orderWithSubOrders, $lineItem->getOrder());
    }

    public function testProductNameIsClearedWhenIsFreeForm(): void
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setProductName('Test Product');
        $orderLineItem->setFreeFormProduct('Free Form Product');

        self::assertSame('Test Product', $orderLineItem->getProductName());

        $orderLineItem->preSave();

        self::assertSame('', $orderLineItem->getProductName(), 'Product name should be cleared when product is null');
    }

    public function testIsFreeFormReturnsFalseByDefault(): void
    {
        $orderLineItem = new OrderLineItem();
        self::assertFalse($orderLineItem->isFreeForm(), 'isFreeForm should return false by default');
    }

    public function testIsFreeFormReturnsTrueWhenFreeFormProductIsSet(): void
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setFreeFormProduct('Custom Product');
        self::assertTrue($orderLineItem->isFreeForm(), 'isFreeForm should return true when freeFormProduct is set');
    }

    public function testIsFreeFormReturnsFalseWhenFreeFormProductIsNull(): void
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setFreeFormProduct(null);
        self::assertFalse($orderLineItem->isFreeForm(), 'isFreeForm should return false when freeFormProduct is null');
    }

    public function testIsFreeFormReturnsTrueWhenWhenExplicitlySetEvenIfFreeFormProductIsNull(): void
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setIsFreeForm(true);
        $orderLineItem->setFreeFormProduct(null);

        self::assertTrue(
            $orderLineItem->isFreeForm(),
            'isFreeForm should return true when explicitly set to true even if freeFormProduct is null'
        );
    }

    public function testSetIsFreeFormToTrueClearsProduct(): void
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setProduct(new ProductStub());
        $orderLineItem->setIsFreeForm(true);
        self::assertNull($orderLineItem->getProduct(), 'Product should be cleared when isFreeForm is set to true');
    }

    public function testSetIsFreeFormToTrueClearsProductSku(): void
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setProductSku('SKU123');
        $orderLineItem->setIsFreeForm(true);
        self::assertNull(
            $orderLineItem->getProductSku(),
            'Product SKU should be cleared when isFreeForm is set to true'
        );
    }

    public function testSetIsFreeFormToTrueClearsProductName(): void
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setProductName('Test Product');
        $orderLineItem->setIsFreeForm(true);
        self::assertSame(
            '',
            $orderLineItem->getProductName(),
            'Product name should be cleared when isFreeForm is set to true'
        );
    }

    public function testSetIsFreeFormToFalseClearsFreeFormProduct(): void
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setFreeFormProduct('Custom Product');
        $orderLineItem->setIsFreeForm(false);
        self::assertNull(
            $orderLineItem->getFreeFormProduct(),
            'Free form product should be cleared when isFreeForm is set to false'
        );
    }

    public function testSetIsFreeFormToFalseClearsProductSku(): void
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setProductSku('SKU123');
        $orderLineItem->setIsFreeForm(false);
        self::assertNull(
            $orderLineItem->getProductSku(),
            'Product SKU should be cleared when isFreeForm is set to false'
        );
    }

    public function testIsFreeFormUsesExplicitValueWhenSet(): void
    {
        $orderLineItem = new OrderLineItem();
        $orderLineItem->setIsFreeForm(true);
        self::assertTrue($orderLineItem->isFreeForm(), 'isFreeForm should return the explicitly set value');
    }
}
