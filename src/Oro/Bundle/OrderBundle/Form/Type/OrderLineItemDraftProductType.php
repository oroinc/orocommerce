<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents the product field of an order line item draft.
 */
class OrderLineItemDraftProductType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(FormEvents::POST_SET_DATA, $this->addKitItemLineItemsOnPostSetData(...))
            ->addEventListener(FormEvents::POST_SET_DATA, $this->replaceProductUnitOnPostSetData(...))
            ->addEventListener(FormEvents::POST_SET_DATA, $this->replacePriceOnPostSetData(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->addKitItemLineItemsOnPostSubmit(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->replaceProductUnitOnPostSubmit(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->replacePriceOnPostSubmit(...));
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'required' => false,
            'autocomplete_alias' => 'oro_order_product_visibility_limited',
            'grid_name' => 'products-select-grid',
            'grid_parameters' => ['types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]],
            'create_enabled' => false,
            'data_parameters' => ['scope' => 'order'],
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ProductSelectType::class;
    }

    private function addKitItemLineItemsOnPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $form->getParent()->getData();
        /** @var Product|null $product */
        $product = $event->getData();
        if ($product !== null) {
            // Checking if the line item is a kit based on the product type.
            $isKit = $product->isKit();
        } elseif ($orderLineItem->isFreeForm()) {
            // Free-form line item cannot be a kit.
            $isKit = false;
        } else {
            // Determines whether the line item is a kit based on the presence of kit item line items.
            $isKit = $orderLineItem->getKitItemLineItems()->count() > 0;
        }

        if ($isKit) {
            /** @var Order $order */
            $order = $orderLineItem->getOrder();
            $currency = $order->getCurrency();

            $form->getParent()->add(
                'kitItemLineItems',
                OrderProductKitItemLineItemCollectionType::class,
                [
                    'required' => true,
                    'product' => $product,
                    'currency' => $currency,
                ]
            );
        }
    }

    private function replaceProductUnitOnPostSetData(FormEvent $event): void
    {
        /** @var Product|null $product */
        $product = $event->getData();
        if (!$product) {
            return;
        }

        $form = $event->getForm();

        // FormUtils::replaceField is not used on purpose as it prevents the initialization of new choices.
        $form->getParent()->add(
            'productUnit',
            ProductUnitSelectionType::class,
            [
                'required' => true,
                'product' => $product,
                'init_choices' => true,
                'auto_initialize' => false,
                'empty_data' => $product->getPrimaryUnitPrecision()?->getProductUnitCode(),
                'sell' => true,
            ]
        );
    }

    private function replacePriceOnPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var Product|null $product */
        $product = $event->getData();
        $isKit = (bool)$product?->isKit();
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $form->getParent()->getData();
        /** @var Order $order */
        $order = $orderLineItem->getOrder();
        $currency = $order->getCurrency();

        FormUtils::replaceField(
            $form->getParent(),
            'price',
            [
                'default_currency' => $currency,
                'readonly' => $isKit,
            ]
        );

        FormUtils::replaceField(
            $form->getParent()->get('price'),
            'is_price_changed',
            [
                'data' => $orderLineItem->getId() ? '1' : '0'
            ]
        );
    }

    private function addKitItemLineItemsOnPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var Product|null $product */
        $product = $form->getData();
        $isKit = (bool)$product?->isKit();
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $form->getParent()->getData();

        if ($isKit) {
            /** @var Order $order */
            $order = $orderLineItem->getOrder();
            $currency = $order->getCurrency();

            if ($orderLineItem->getProduct() !== $product) {
                // Kit items collection should be cleared and totally replaced if the product is changed.
                $orderLineItem->getKitItemLineItems()->clear();
                $orderLineItem->setKitItemLineItems(new ArrayCollection());
            }

            $form->getParent()->add(
                'kitItemLineItems',
                OrderProductKitItemLineItemCollectionType::class,
                [
                    'required' => true,
                    'product' => $product,
                    'currency' => $currency,
                ]
            );
        } else {
            $form->getParent()->remove('kitItemLineItems');
            $orderLineItem->getKitItemLineItems()->clear();
        }
    }

    private function replaceProductUnitOnPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var Product|null $product */
        $product = $form->getData();

        // FormUtils::replaceField is not used on purpose as it prevents the initialization of new choices.
        $form->getParent()->add(
            'productUnit',
            ProductUnitSelectionType::class,
            [
                'required' => true,
                'product' => $product,
                'init_choices' => true,
                'auto_initialize' => false,
                'empty_data' => $product?->getPrimaryUnitPrecision()?->getProductUnitCode(),
                'sell' => true,
            ]
        );
    }

    private function replacePriceOnPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var Product|null $product */
        $product = $form->getData();
        $isKit = (bool)$product?->isKit();
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $form->getParent()->getData();
        /** @var Order $order */
        $order = $orderLineItem->getOrder();
        $currency = $order->getCurrency();

        FormUtils::replaceField(
            $form->getParent(),
            'price',
            [
                'default_currency' => $currency,
                'readonly' => $isKit,
            ]
        );
    }
}
