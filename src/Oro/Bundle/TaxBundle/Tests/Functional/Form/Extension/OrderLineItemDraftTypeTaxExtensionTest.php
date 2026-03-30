<?php

declare(strict_types=1);

namespace Oro\Bundle\TaxBundle\Tests\Functional\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemDraftType;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Form\Type\ProductTaxCodeAutocompleteType;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;
use Oro\Bundle\TestFrameworkBundle\Test\Form\FormAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class OrderLineItemDraftTypeTaxExtensionTest extends WebTestCase
{
    use FormAwareTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadOrders::class,
            LoadProductData::class,
            LoadProductTaxCodes::class,
        ]);
    }

    public function testAddsFreeFormTaxCodeWhenOrderLineItemInFreeFormMode(): void
    {
        /** @var Order $order */
        $order = $this->getReference('simple_order');
        $lineItem = new OrderLineItem();
        $lineItem->setFreeFormProduct('Free Form Product');
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);

        self::assertTrue($form->has('freeFormTaxCode'), 'freeFormTaxCode field should be present');
        self::assertFormHasField(
            $form,
            'freeFormTaxCode',
            ProductTaxCodeAutocompleteType::class,
            [
                'required' => false,
                'create_enabled' => false,
                'label' => 'oro.order.orderlineitem.free_form_tax_code.label',
                'tooltip' => 'oro.order.orderlineitem.free_form_tax_code.description',
            ]
        );
    }

    public function testAddsFreeFormTaxCodeWhenIsFreeFormTrue(): void
    {
        /** @var \Oro\Bundle\OrderBundle\Entity\Order $order */
        $order = $this->getReference('simple_order');
        $lineItem = new OrderLineItem();
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);
        $form->submit([
            'isFreeForm' => true,
            'freeFormProduct' => 'Test Product',
            'quantity' => 1,
            'productUnit' => 'item',
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        self::assertTrue($form->has('freeFormTaxCode'), 'freeFormTaxCode field should be present after submission');
    }

    public function testRemovesFreeFormTaxCodeWhenIsFreeFormFalse(): void
    {
        /** @var Order $order */
        $order = $this->getReference('simple_order');
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $lineItem = new OrderLineItem();
        $lineItem->setFreeFormProduct('Free Form Product');
        $order->addLineItem($lineItem);

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);
        self::assertTrue($form->has('freeFormTaxCode'));

        $form->submit([
            'isFreeForm' => false,
            'product' => $product->getId(),
            'quantity' => 1,
            'productUnit' => 'item',
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        self::assertFalse(
            $form->has('freeFormTaxCode'),
            'freeFormTaxCode field should not be present when isFreeForm is false'
        );
    }

    public function testClearsFreeFormTaxCodeWhenTriggerIsFreeForm(): void
    {
        /** @var Order $order */
        $order = $this->getReference('simple_order');
        /** @var ProductTaxCode $taxCode */
        $taxCode = $this->getReference(
            LoadProductTaxCodes::REFERENCE_PREFIX . '.' .
            LoadProductTaxCodes::TAX_1
        );

        $lineItem = new OrderLineItem();
        $lineItem->setFreeFormProduct('Test Product');
        $lineItem->setFreeFormTaxCode($taxCode);
        $order->addLineItem($lineItem);

        self::assertNotNull($lineItem->getFreeFormTaxCode(), 'freeFormTaxCode should be set initially');

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);
        $form->submit([
            'drySubmitTrigger' => 'isFreeForm',
            'isFreeForm' => false,
            'freeFormTaxCode' => $taxCode->getId(),
            'quantity' => 1,
            'productUnit' => 'item',
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        self::assertNull(
            $lineItem->getFreeFormTaxCode(),
            'freeFormTaxCode should be cleared when isFreeForm trigger is used'
        );

        self::assertFalse(
            $form->has('freeFormTaxCode'),
            'freeFormTaxCode field should not be present when isFreeForm is false'
        );
    }

    public function testClearsFreeFormTaxCodeWhenIsFreeFormFalse(): void
    {
        /** @var Order $order */
        $order = $this->getReference('simple_order');
        /** @var ProductTaxCode $taxCode */
        $taxCode = $this->getReference(
            LoadProductTaxCodes::REFERENCE_PREFIX . '.' .
            LoadProductTaxCodes::TAX_1
        );

        $lineItem = new OrderLineItem();
        $lineItem->setFreeFormProduct('Test Product');
        $lineItem->setFreeFormTaxCode($taxCode);
        $order->addLineItem($lineItem);

        self::assertNotNull($lineItem->getFreeFormTaxCode(), 'freeFormTaxCode should be set initially');

        $form = self::createForm(OrderLineItemDraftType::class, $lineItem);
        $form->submit([
            'drySubmitTrigger' => 'quantity',
            'isFreeForm' => false,
            'freeFormTaxCode' => $taxCode->getId(),
            'quantity' => 2,
            'productUnit' => 'item',
            'price' => ['value' => 10, 'currency' => 'USD'],
        ]);

        self::assertNull(
            $lineItem->getFreeFormTaxCode(),
            'freeFormTaxCode should be cleared when isFreeForm is false'
        );

        self::assertFalse(
            $form->has('freeFormTaxCode'),
            'freeFormTaxCode field should not be present when isFreeForm is false'
        );
    }
}
