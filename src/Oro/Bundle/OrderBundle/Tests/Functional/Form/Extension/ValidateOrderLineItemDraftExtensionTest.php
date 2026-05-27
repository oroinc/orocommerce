<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Constraints\GroupSequence;

/**
 * @dbIsolationPerTest
 */
final class ValidateOrderLineItemDraftExtensionTest extends WebTestCase
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
        ]);
    }

    public function testInitialValidationOptionDefaultsToTrue(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertTrue(
            $form->getConfig()->getOption('initial_validation'),
            'initial_validation option should default to true'
        );
    }

    public function testInitialValidationOptionCanBeSetToFalse(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem, [
            'initial_validation' => false,
        ]);

        self::assertFalse(
            $form->getConfig()->getOption('initial_validation'),
            'initial_validation option should be false when explicitly set'
        );
    }

    public function testValidationGroupsOptionIsCallable(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertIsCallable(
            $form->getConfig()->getOption('validation_groups'),
            'validation_groups option should be a callable'
        );
    }

    public function testValidationErrorsArePresentOnInitialRenderingForInvalidExistingLineItem(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create an existing line item with invalid data (missing required fields)
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity(null); // Invalid: quantity is required
        $order->addLineItem($lineItem);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        // Validation errors should be present on the form
        self::assertGreaterThan(
            0,
            count($form->getErrors(true)),
            'Form should have validation errors on initial rendering for invalid existing line item'
        );
    }

    public function testNoValidationErrorsForValidExistingLineItem(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create a valid existing line item
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity(5);
        $lineItem->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $lineItem->setPrice(Price::create(10.00, 'USD'));
        $order->addLineItem($lineItem);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        // No validation errors should be present
        self::assertCount(
            0,
            $form->getErrors(true),
            'Form should not have validation errors for valid existing line item'
        );
    }

    public function testValidationOnInitialRenderingForNewLineItem(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);

        // Create a new line item without an ID (even with invalid data)
        $lineItem = new OrderLineItem();
        $lineItem->setQuantity(null); // Invalid - should be validated on initial rendering
        $order->addLineItem($lineItem);

        // Create form without submitting
        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertGreaterThan(
            0,
            count($form->getErrors(true)),
            'Form should have validation errors for new line items on initial rendering'
        );
    }

    public function testNoValidationWhenInitialValidationOptionIsFalse(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create an existing line item with invalid data
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity(null); // Invalid
        $order->addLineItem($lineItem);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        // Create form with initial_validation = false
        $form = self::createForm(OrderLineItemDraftType::class, $lineItem, [
            'initial_validation' => false,
        ]);

        // No validation errors should be present
        self::assertCount(
            0,
            $form->getErrors(true),
            'Form should not validate when initial_validation is false'
        );
    }

    public function testUsesUpdateValidationGroupForExistingChangedLineItem(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var Product $anotherProduct */
        $anotherProduct = $this->getReference(LoadProductData::PRODUCT_2);

        // Create and persist a valid existing line item
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity(5);
        $lineItem->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $lineItem->setPrice(Price::create(10.00, 'USD'));
        $order->addLineItem($lineItem);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        // Change product after flush - EntityStateChecker will detect the change,
        // resulting in validation groups: [Default, 'order_line_item_update']
        $lineItem->setProduct($anotherProduct);
        $lineItem->setProductUnit($anotherProduct->getPrimaryUnitPrecision()->getUnit());

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        // No validation errors for valid data
        self::assertCount(
            0,
            $form->getErrors(true),
            'Valid line item with changed product in update mode should have no errors'
        );
    }

    public function testUsesDefaultValidationGroupForUnchangedExistingLineItem(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create and persist a valid existing line item
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity(5);
        $lineItem->setProductUnit($product->getPrimaryUnitPrecision()->getUnit());
        $lineItem->setPrice(Price::create(10.00, 'USD'));
        $order->addLineItem($lineItem);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        // No changes after flush - EntityStateChecker detects no changes,
        // resulting in validation groups: [Default] only
        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        // No validation errors for valid unchanged data
        self::assertCount(
            0,
            $form->getErrors(true),
            'Unchanged existing line item should have no validation errors'
        );
    }

    public function testValidationErrorsAreMappedToCorrectFormFields(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        // Create an existing line item with multiple validation errors
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity(null); // Invalid: required
        $lineItem->setProductUnit(null); // Invalid: required
        $order->addLineItem($lineItem);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $em->persist($lineItem);
        $em->flush();

        // Create form
        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        // Check that errors are present
        $errors = $form->getErrors(true);
        self::assertGreaterThan(
            0,
            count($errors),
            'Form should have validation errors'
        );

        // Verify errors are mapped to specific fields
        $hasQuantityError = false;
        $hasProductUnitError = false;

        foreach ($errors as $error) {
            $errorPath = $error->getOrigin()?->getName();
            if ($errorPath === 'quantity') {
                $hasQuantityError = true;
            }
            if ($errorPath === 'productUnit') {
                $hasProductUnitError = true;
            }
        }

        self::assertTrue(
            $hasQuantityError || $hasProductUnitError,
            'Validation errors should be properly mapped to form fields'
        );
    }

    public function testValidationGroupsForNewLineItem(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        $config = $form->getConfig();
        $validationGroups = $config->getOption('validation_groups');

        self::assertEquals(new GroupSequence(['Default', 'order_line_item_create']), $validationGroups($form));
    }

    public function testValidationGroupsForUpdatedLineItemProduct(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productUnit = $this->getReference('product_unit.liter');

        // Create and persist a line item to ensure it's tracked by Doctrine
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setQuantity(1);
        $lineItem->setChecksum('original_checksum');
        $lineItem->setPrice(Price::create(10, 'USD'));
        $order->addLineItem($lineItem);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $entityManager->persist($lineItem);
        $entityManager->flush($lineItem);

        $newProduct = $this->getReference(LoadProductData::PRODUCT_2);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        $form->submit([
            'product' => $newProduct->getId(),
            'quantity' => 1,
            'productUnit' => $productUnit->getCode(),
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        $config = $form->getConfig();
        $validationGroups = $config->getOption('validation_groups');

        self::assertEquals(new GroupSequence(['Default', 'order_line_item_update']), $validationGroups($form));
    }

    public function testValidationGroupsForUpdatedLineItemQuantity(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productUnit = $this->getReference('product_unit.liter');

        // Create and persist a line item to ensure it's tracked by Doctrine
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setQuantity(1);
        $lineItem->setChecksum('original_checksum');
        $order->addLineItem($lineItem);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $entityManager->persist($lineItem);
        $entityManager->flush($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        $form->submit([
            'product' => $product->getId(),
            'quantity' => 10,
            'productUnit' => $productUnit->getCode(),
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        $config = $form->getConfig();
        $validationGroups = $config->getOption('validation_groups');

        self::assertEquals(new GroupSequence(['Default', 'order_line_item_update']), $validationGroups($form));
    }

    public function testValidationGroupsForUnchangedLineItem(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productUnit = $this->getReference('product_unit.liter');

        // Create and persist a line item to ensure it's tracked by Doctrine
        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setProductUnit($productUnit);
        $lineItem->setQuantity(1);
        $lineItem->setChecksum('original_checksum');
        $order->addLineItem($lineItem);

        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(OrderLineItem::class);
        $entityManager->persist($lineItem);
        $entityManager->flush($lineItem);

        // Do not change product or checksum - entity should be tracked as unchanged
        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        $config = $form->getConfig();
        $validationGroups = $config->getOption('validation_groups');

        self::assertEquals(new GroupSequence(['Default']), $validationGroups($form));
    }

    public function testValidationGroupsForDrySubmit(): void
    {
        $order = $this->getReference(LoadOrders::ORDER_1);
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        $form->submit([
            'drySubmitTrigger' => 'product',
            'quantity' => 1,
            'productUnit' => 'item',
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        $config = $form->getConfig();
        $validationGroups = $config->getOption('validation_groups');

        self::assertEquals(['order_line_item_draft_dry_submit'], $validationGroups($form));
    }
}
