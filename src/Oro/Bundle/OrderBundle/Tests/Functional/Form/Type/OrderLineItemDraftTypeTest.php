<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\OrderBundle\Form\Type\OrderPriceType;
use Oro\Bundle\OrderBundle\Form\Type\OrderProductKitItemLineItemCollectionType;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * @dbIsolationPerTest
 */
final class OrderLineItemDraftTypeTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrders::class,
            LoadProductData::class,
            LoadProductUnits::class,
            LoadProductKitData::class,
        ]);
    }

    public function testFormHasAllRequiredFields(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertTrue($form->has('drySubmitTrigger'));
        self::assertTrue($form->has('isFreeForm'));
        self::assertTrue($form->has('product'));
        self::assertTrue($form->has('quantity'));
        self::assertTrue($form->has('productUnit'));
        self::assertTrue($form->has('price'));
        self::assertTrue($form->has('priceType'));
        self::assertTrue($form->has('shipBy'));
        self::assertTrue($form->has('comment'));
    }

    public function testDrySubmitTriggerFieldConfiguration(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'drySubmitTrigger', HiddenType::class, [
            'mapped' => false,
        ]);
    }

    public function testIsFreeFormFieldConfiguration(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'isFreeForm', HiddenType::class);
    }

    public function testProductFieldConfiguration(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'product', ProductSelectType::class, [
            'autocomplete_alias' => 'oro_order_product_visibility_limited',
            'grid_name' => 'products-select-grid',
            'grid_parameters' => ['types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]],
            'create_enabled' => false,
            'data_parameters' => ['scope' => 'order'],
        ]);
    }

    public function testQuantityFieldConfiguration(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'quantity', QuantityType::class, [
            'required' => true,
            'default_data' => 1
        ]);
    }

    public function testProductUnitFieldConfiguration(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'productUnit', ProductUnitSelectionType::class, [
            'required' => true,
            'sell' => true
        ]);
    }

    public function testPriceFieldConfiguration(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'price', OrderPriceType::class, [
            'required' => true,
            'error_bubbling' => true,
            'hide_currency' => true,
        ]);
    }

    public function testPriceTypeFieldConfiguration(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'priceType', HiddenType::class, [
            'data' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
        ]);
    }

    public function testShipByFieldConfiguration(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'shipBy', OroDateType::class, [
            'required' => false,
        ]);
    }

    public function testCommentFieldConfiguration(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'comment', TextareaType::class, [
            'required' => false,
        ]);
    }

    public function testFreeFormFieldsAreAddedWhenHasFreeFormProduct(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $lineItem->setFreeFormProduct('Free Form Product');
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'productSku', TextType::class, ['required' => true]);
        self::assertFormHasField($form, 'freeFormProduct', TextType::class, ['required' => true]);
    }

    public function testFreeFormFieldsAreAddedWhenIsFreeFormIsTrue(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $lineItem->setIsFreeForm(true);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFormHasField($form, 'productSku', TextType::class, ['required' => true]);
        self::assertFormHasField($form, 'freeFormProduct', TextType::class, ['required' => true]);
    }

    public function testFreeFormFieldsAreNotAddedWhenNoFreeFormProduct(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFalse($form->has('productSku'), 'productSku field should not be present when isFreeForm is false');
        self::assertFalse(
            $form->has('freeFormProduct'),
            'freeFormProduct field should not be present when isFreeForm is false'
        );
    }

    public function testKitItemLineItemsFieldIsAddedForKitProduct(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $kitProduct */
        $kitProduct = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($kitProduct);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertTrue($form->has('kitItemLineItems'), 'kitItemLineItems field should be present for kit products');
        self::assertFormHasField($form, 'kitItemLineItems', OrderProductKitItemLineItemCollectionType::class, [
            'required' => true,
        ]);
    }

    public function testKitItemLineItemsFieldIsAddedForMissingProductWithKitItems(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $kitProduct */
        $kitProduct = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        /** @var Product $simpleProduct */
        $simpleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProductSku('MISSING-PRODUCT-SKU');
        $lineItem->setProductName('Missing Product');
        $kitItemLineItem1 = new OrderProductKitItemLineItem();
        $kitItemLineItem1->setKitItem($kitProduct->getKitItems()->first());
        $kitItemLineItem1->setProduct($simpleProduct);
        $kitItemLineItem1->setQuantity(1);
        $lineItem->addKitItemLineItem($kitItemLineItem1);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertTrue($form->has('kitItemLineItems'), 'kitItemLineItems field should be present for kit products');
        self::assertFormHasField($form, 'kitItemLineItems', OrderProductKitItemLineItemCollectionType::class, [
            'required' => true,
        ]);
    }

    public function testKitItemLineItemsFieldIsNotAddedForFreeFormProduct(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $kitProduct */
        $kitProduct = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        /** @var Product $simpleProduct */
        $simpleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProductSku('FREE-FORM-PRODUCT-SKU');
        $lineItem->setFreeFormProduct('Free Form Product');
        $kitItemLineItem1 = new OrderProductKitItemLineItem();
        $kitItemLineItem1->setKitItem($kitProduct->getKitItems()->first());
        $kitItemLineItem1->setProduct($simpleProduct);
        $kitItemLineItem1->setQuantity(1);
        $lineItem->addKitItemLineItem($kitItemLineItem1);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFalse(
            $form->has('kitItemLineItems'),
            'kitItemLineItems field should not be present for free-form product'
        );
    }

    public function testKitItemLineItemsFieldIsNotAddedForSimpleProduct(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertFalse(
            $form->has('kitItemLineItems'),
            'kitItemLineItems field should not be present for simple products'
        );
    }

    public function testSubmitValidData(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        /** @var ProductUnit $liter */
        $liter = $this->getReference(LoadProductUnits::LITER);

        $formData = [
            'product' => $product->getId(),
            'quantity' => 5,
            'productUnit' => $liter->getCode(),
            'price' => [
                'value' => 10.50,
                'currency' => 'USD',
            ],
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
            'comment' => 'Test comment',
        ];

        $form->submit($formData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertSame($product, $lineItem->getProduct());
        self::assertEquals(5, $lineItem->getQuantity());
        self::assertEquals($liter, $lineItem->getProductUnit());
        self::assertEquals('Test comment', $lineItem->getComment());
    }

    public function testSubmitValidFreeFormData(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);

        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        $formData = [
            'isFreeForm' => true,
            'productSku' => 'SKU-123',
            'freeFormProduct' => 'Custom Product',
            'quantity' => 3,
            'productUnit' => 'item',
            'price' => [
                'value' => 25.00,
                'currency' => 'USD',
            ],
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
        ];

        $form->submit($formData);

        self::assertTrue($form->isValid(), (string)$form->getErrors(true, true));
        self::assertTrue($form->isSynchronized());
        self::assertEquals('SKU-123', $lineItem->getProductSku());
        self::assertEquals('Custom Product', $lineItem->getFreeFormProduct());
        self::assertEquals(3, $lineItem->getQuantity());
    }

    public function testProductUnitIsReplacedWhenProductChanges(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        // Clears additional unit precisions to ensure only default unit is available for selection.
        $product2->getUnitPrecisions()->clear();

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product1);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);
        self::assertEquals(
            [
                $this->getReference(LoadProductUnits::MILLILITER),
                $this->getReference(LoadProductUnits::LITER),
                $this->getReference(LoadProductUnits::BOTTLE),
            ],
            $form->get('productUnit')->getConfig()->getOption('choices')
        );

        // Change product
        $form->submit([
            'product' => $product2->getId(),
            'quantity' => 1,
            'productUnit' => 'item',
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        self::assertEquals(
            [
                $this->getReference(LoadProductUnits::MILLILITER),
            ],
            $form->get('productUnit')->getConfig()->getOption('choices')
        );
    }

    public function testPriceIsReadonlyForKitProducts(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $kitProduct */
        $kitProduct = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($kitProduct);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        $priceField = $form->get('price');
        $priceConfig = $priceField->getConfig();

        self::assertTrue($priceConfig->getOption('readonly'), 'Price should be readonly for kit products');
    }

    public function testPriceIsNotReadonlyForSimpleProducts(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        $priceField = $form->get('price');
        $priceConfig = $priceField->getConfig();

        self::assertFalse($priceConfig->getOption('readonly'), 'Price should not be readonly for simple products');
    }

    public function testFreeFormFieldsAreRemovedWhenIsFreeFormSetToFalse(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $lineItem->setFreeFormProduct('Initial Free Form Product');
        $lineItem->setProductSku('INITIAL-SKU');
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        // Initially, free form fields should be present
        self::assertTrue($form->has('productSku'));
        self::assertTrue($form->has('freeFormProduct'));

        // Submit with isFreeForm = false
        $form->submit([
            'isFreeForm' => false,
            'quantity' => 1,
            'productUnit' => 'item',
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        self::assertFalse($form->has('productSku'));
        self::assertFalse($form->has('freeFormProduct'));

        // Free form values should be cleared
        self::assertNull($lineItem->getProductSku());
        self::assertNull($lineItem->getFreeFormProduct());
    }

    public function testIsFreeFormFieldsAsFilledWithPreviousProduct(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        // Initially, free form fields should not be present
        self::assertFalse($form->has('productSku'));
        self::assertFalse($form->has('freeFormProduct'));

        // Submit with isFreeForm = true
        $form->submit([
            'isFreeForm' => true,
            'product' => $product->getId(),
            'quantity' => 1,
            'productUnit' => 'item',
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        self::assertTrue($form->has('productSku'));
        self::assertTrue($form->has('freeFormProduct'));

        // Product fields should be cleared.
        self::assertNull($lineItem->getProduct());
        self::assertSame('', $lineItem->getProductName());

        // Free-form fields should be filled with previous product data.
        self::assertEquals($lineItem->getProductSku(), $product->getSku());
        self::assertEquals($lineItem->getFreeFormProduct(), $product->getDenormalizedDefaultName());
    }

    public function testBlockPrefix(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);
        $formView = $form->createView();

        self::assertContains('oro_order_line_item_draft', $formView->vars['block_prefixes']);
    }

    public function testKitItemLineItemsAreRemovedWhenProductChangesToNonKit(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $kitProduct */
        $kitProduct = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        /** @var Product $simpleProduct */
        $simpleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($kitProduct);
        $kitItemLineItem1 = new OrderProductKitItemLineItem();
        $kitItemLineItem1->setKitItem($kitProduct->getKitItems()->first());
        $kitItemLineItem1->setProduct($simpleProduct);
        $kitItemLineItem1->setQuantity(1);
        $lineItem->addKitItemLineItem($kitItemLineItem1);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        // Initially, kit field should be present
        self::assertTrue($form->has('kitItemLineItems'), 'kitItemLineItems field should be present for kit product');

        // Change to simple product
        $form->submit([
            'product' => $simpleProduct->getId(),
            'quantity' => 1,
            'productUnit' => 'item',
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        // After submission, the kitItemLineItems field should be removed from the form
        self::assertFalse(
            $form->has('kitItemLineItems'),
            'kitItemLineItems field should be removed when product changes to non-kit'
        );

        // And the kitItemLineItems collection should be cleared from the entity
        self::assertCount(0, $lineItem->getKitItemLineItems(), 'kitItemLineItems collection should be cleared');
    }

    public function testKitItemLineItemsAreClearedWhenProductChangesToAnotherKit(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $kitProduct1 */
        $kitProduct1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        /** @var Product $kitProduct2 */
        $kitProduct2 = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        /** @var Product $simpleProduct */
        $simpleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($kitProduct1);
        $kitItemLineItem1 = new OrderProductKitItemLineItem();
        $kitItemLineItem1->setKitItem($kitProduct1->getKitItems()->first());
        $kitItemLineItem1->setProduct($simpleProduct);
        $kitItemLineItem1->setQuantity(1);
        $lineItem->addKitItemLineItem($kitItemLineItem1);
        $order->addLineItem($lineItem);

        $originalKitItemLineItems = $lineItem->getKitItemLineItems();

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertTrue($form->has('kitItemLineItems'), 'kitItemLineItems field should be present for kit product');

        // Change to another product kit
        $form->submit([
            'product' => $kitProduct2->getId(),
            'quantity' => 1,
            'productUnit' => 'item',
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        self::assertTrue(
            $form->has('kitItemLineItems'),
            'kitItemLineItems field should still be present'
        );

        self::assertNotSame(
            $lineItem->getKitItemLineItems(),
            $originalKitItemLineItems,
            'kitItemLineItems collection should be replaced with new collection'
        );
    }

    public function testIsFreeFormSetterConvertsStringZeroToFalse(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        /** @var ProductUnit $liter */
        $liter = $this->getReference(LoadProductUnits::LITER);

        // Submit with string "0" as JavaScript/HTTP would send
        $form->submit([
            'isFreeForm' => '0',
            'product' => $product->getId(),
            'quantity' => 1,
            'productUnit' => $liter->getCode(),
            'price' => ['value' => 10, 'currency' => 'USD'],
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
        ]);

        self::assertTrue($form->isValid(), 'Form should be valid');
        self::assertTrue($form->isSynchronized(), 'Form should be synchronized');
        self::assertFalse($lineItem->isFreeForm(), 'String "0" should be converted to false by setter');

        // Verify free form fields are not present
        self::assertFalse($form->has('productSku'), 'productSku should not be present when isFreeForm is false');
        self::assertFalse(
            $form->has('freeFormProduct'),
            'freeFormProduct should not be present when isFreeForm is false'
        );

        // Verify product is still set
        self::assertSame($product, $lineItem->getProduct());
    }

    public function testIsFreeFormSetterConvertsStringOneToTrue(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        // Submit with string "1" as JavaScript/HTTP would send
        $form->submit([
            'isFreeForm' => '1',
            'freeFormProduct' => 'Test Free Form Product',
            'productSku' => 'SKU-TEST-001',
            'quantity' => 5,
            'productUnit' => 'item',
            'price' => ['value' => 25.50, 'currency' => 'USD'],
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
        ]);

        self::assertTrue($form->isValid(), 'Form should be valid');
        self::assertTrue($form->isSynchronized(), 'Form should be synchronized');
        self::assertTrue($lineItem->isFreeForm(), 'String "1" should be converted to true by setter');

        // Verify free form data is set
        self::assertEquals('SKU-TEST-001', $lineItem->getProductSku());
        self::assertEquals('Test Free Form Product', $lineItem->getFreeFormProduct());
        self::assertEquals(5, $lineItem->getQuantity());
    }

    public function testIsFreeFormSetterConvertsEmptyStringToFalse(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        /** @var ProductUnit $liter */
        $liter = $this->getReference(LoadProductUnits::LITER);

        // Submit with empty string (can happen with some JS frameworks)
        $form->submit([
            'isFreeForm' => '',
            'product' => $product->getId(),
            'quantity' => 2,
            'productUnit' => $liter->getCode(),
            'price' => ['value' => 15.00, 'currency' => 'USD'],
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
        ]);

        self::assertTrue($form->isValid(), 'Form should be valid');
        self::assertTrue($form->isSynchronized(), 'Form should be synchronized');
        self::assertFalse($lineItem->isFreeForm(), 'Empty string should be converted to false');

        // Verify product is still set
        self::assertSame($product, $lineItem->getProduct());
    }

    public function testIsPriceChangedIsSetToZeroForNewLineItem(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertTrue($form->get('price')->has('is_price_changed'), 'is_price_changed field should be present');

        $isPriceChangedField = $form->get('price')->get('is_price_changed');
        $isPriceChangedData = $isPriceChangedField->getData();

        self::assertEquals('0', $isPriceChangedData, 'is_price_changed should be "0" for new line items');
    }

    public function testIsPriceChangedIsSetToOneForExistingLineItem(): void
    {
        /** @var Order $order */
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create and persist a line item
        $entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(OrderLineItem::class);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity(1);
        /** @var ProductUnit $liter */
        $liter = $this->getReference(LoadProductUnits::LITER);
        $lineItem->setProductUnit($liter);
        $lineItem->setPrice(Price::create(10.00, 'USD'));
        $lineItem->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_UNIT);
        $order->addLineItem($lineItem);

        $entityManager->persist($lineItem);
        $entityManager->flush();

        self::assertNotNull($lineItem->getId(), 'Line item should have an ID after persistence');

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertTrue($form->get('price')->has('is_price_changed'), 'is_price_changed field should be present');

        $isPriceChangedField = $form->get('price')->get('is_price_changed');
        $isPriceChangedData = $isPriceChangedField->getData();

        self::assertEquals('1', $isPriceChangedData, 'is_price_changed should be "1" for existing line items');
    }

    public function testIsPriceChangedClearedWhenDrySubmitTriggerIsProduct(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product1);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        /** @var ProductUnit $liter */
        $liter = $this->getReference(LoadProductUnits::LITER);

        // Submit with drySubmitTrigger set to 'product' and is_price_changed set to '1'
        $form->submit([
            'drySubmitTrigger' => 'product',
            'isFreeForm' => '0',
            'product' => $product2->getId(),
            'quantity' => 1,
            'productUnit' => $liter->getCode(),
            'price' => ['value' => 10.50, 'currency' => 'USD', 'is_price_changed' => '1'],
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
        ]);

        self::assertTrue($form->isSubmitted(), 'Form should be submitted');

        // Verify that price value and is_price_changed were cleared by the dry submit listener
        $submittedData = $form->getData();

        // When dry submit trigger is 'product' and isFreeForm is false,
        // the price value should be cleared by the listener
        self::assertNull($submittedData->getPrice(), 'Price should be null when cleared by dry submit listener');
    }

    public function testIsPriceChangedClearedWhenDrySubmitTriggerIsIsFreeForm(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        /** @var ProductUnit $liter */
        $liter = $this->getReference(LoadProductUnits::LITER);

        // Submit with drySubmitTrigger set to 'isFreeForm' with isFreeForm = false
        $form->submit([
            'drySubmitTrigger' => 'isFreeForm',
            'isFreeForm' => '0',
            'product' => $product->getId(),
            'quantity' => 1,
            'productUnit' => $liter->getCode(),
            'price' => ['value' => 10.50, 'currency' => 'USD', 'is_price_changed' => '1'],
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
        ]);

        self::assertTrue($form->isSubmitted(), 'Form should be submitted');

        // Verify that price value and is_price_changed were cleared by the dry submit listener
        $submittedData = $form->getData();

        // When dry submit trigger is 'isFreeForm' and isFreeForm is false,
        // the price value should be cleared by the listener
        self::assertNull($submittedData->getPrice(), 'Price should be null when cleared by dry submit listener');
    }

    public function testProductKitLineItemPriceIsNotClearedWhenIsPriceChangedIsTrue(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $kitProduct */
        $kitProduct = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        /** @var Product $simpleProduct */
        $simpleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($kitProduct);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        /** @var ProductUnit $bottle */
        $bottle = $this->getReference(LoadProductUnits::BOTTLE);

        // Get the first kit item from the kit product
        $kitItem = $kitProduct->getKitItems()->first();

        // Submit with drySubmitTrigger set to kit item line item field and is_price_changed = true
        $form->submit([
            'drySubmitTrigger' => 'kitItemLineItems[0][quantity]',
            'isFreeForm' => '0',
            'product' => $kitProduct->getId(),
            'quantity' => 1,
            'productUnit' => $bottle->getCode(),
            'price' => ['value' => 50.00, 'currency' => 'USD', 'is_price_changed' => '1'],
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
            'kitItemLineItems' => [
                [
                    'kitItem' => $kitItem->getId(),
                    'product' => $simpleProduct->getId(),
                    'quantity' => 2,
                ],
            ],
        ]);

        self::assertTrue($form->isSubmitted(), 'Form should be submitted');

        $submittedData = $form->getData();

        // When is_price_changed is true (manually set price), the price should NOT be cleared
        // even when kit item line item fields trigger dry submit
        self::assertNotNull($submittedData->getPrice(), 'Price should not be cleared when is_price_changed is true');
        self::assertEquals(50.00, $submittedData->getPrice()->getValue(), 'Price value should be preserved');
    }

    public function testProductKitLineItemPriceIsClearedWhenIsPriceChangedIsFalse(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $kitProduct */
        $kitProduct = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        /** @var Product $simpleProduct */
        $simpleProduct = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($kitProduct);
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        /** @var ProductUnit $bottle */
        $bottle = $this->getReference(LoadProductUnits::BOTTLE);

        // Get the first kit item from the kit product
        $kitItem = $kitProduct->getKitItems()->first();

        // Submit with drySubmitTrigger set to kit item line item field and is_price_changed = false (or not set)
        $form->submit([
            'drySubmitTrigger' => 'kitItemLineItems[0][quantity]',
            'isFreeForm' => '0',
            'product' => $kitProduct->getId(),
            'quantity' => 1,
            'productUnit' => $bottle->getCode(),
            'price' => ['value' => 50.00, 'currency' => 'USD', 'is_price_changed' => '0'],
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_UNIT,
            'kitItemLineItems' => [
                [
                    'kitItem' => $kitItem->getId(),
                    'product' => $simpleProduct->getId(),
                    'quantity' => 2,
                ],
            ],
        ]);

        self::assertTrue($form->isSubmitted(), 'Form should be submitted');

        $submittedData = $form->getData();

        // When is_price_changed is false (automatically calculated price),
        // the price should be cleared when kit item line item fields trigger dry submit
        // so it can be recalculated
        self::assertNull($submittedData->getPrice(), 'Price should be cleared when is_price_changed is false');
    }
}
