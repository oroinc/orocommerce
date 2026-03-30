<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Provider\OrderLineItemTierPricesProvider;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents form type for order line item draft.
 */
final class OrderLineItemDraftType extends AbstractType
{
    public function __construct(
        private readonly OrderLineItemTierPricesProvider $tierPricesProvider,
        private readonly EventSubscriberInterface $orderLineItemDraftDrySubmitListener,
        private readonly EventSubscriberInterface $orderLineItemDraftChecksumListener
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('drySubmitTrigger', HiddenType::class, ['mapped' => false])
            ->add('isFreeForm', HiddenType::class, [
                'setter' => static function (OrderLineItem $orderLineItem, $value): void {
                    // Convert string "0"/"1" to bool for strict_types compatibility
                    $orderLineItem->setIsFreeForm((bool)(int)$value);
                },
            ])
            ->add(
                'product',
                ProductSelectType::class,
                [
                    'required' => false,
                    'autocomplete_alias' => 'oro_order_product_visibility_limited',
                    'grid_name' => 'products-select-grid',
                    'grid_parameters' => ['types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]],
                    'create_enabled' => false,
                    'data_parameters' => ['scope' => 'order'],
                ]
            )
            ->add('quantity', QuantityType::class, ['required' => true, 'default_data' => 1])
            ->add('productUnit', ProductUnitSelectionType::class, ['required' => true, 'sell' => true])
            ->add(
                'price',
                OrderPriceType::class,
                [
                    'required' => true,
                    'error_bubbling' => true,
                    'hide_currency' => true,
                ]
            )
            ->add('priceType', HiddenType::class, ['data' => PriceTypeAwareInterface::PRICE_TYPE_UNIT])
            ->add('shipBy', OroDateType::class, ['required' => false])
            ->add('comment', TextareaType::class, ['required' => false]);

        $builder
            ->get('isFreeForm')
            ->addEventListener(FormEvents::POST_SET_DATA, $this->addFreeFormProductOnIsFreeFormPostSetData(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->addFreeFormProductOnIsFreeFormPostSubmit(...));

        $builder
            ->get('product')
            ->addEventListener(FormEvents::POST_SET_DATA, $this->addKitItemLineItemsOnProductPostSetData(...))
            ->addEventListener(FormEvents::POST_SET_DATA, $this->replaceProductUnitOnProductPostSetData(...))
            ->addEventListener(FormEvents::POST_SET_DATA, $this->replacePriceOnProductPostSetData(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->addKitItemLineItemsOnProductPostSubmit(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->replaceProductUnitOnProductPostSubmit(...))
            ->addEventListener(FormEvents::POST_SUBMIT, $this->replacePriceOnProductPostSubmit(...));

        $builder->addEventListener(FormEvents::POST_SUBMIT, $this->fillFreeFormProductOnPostSubmit(...));

        $builder->addEventSubscriber($this->orderLineItemDraftDrySubmitListener);
        $builder->addEventSubscriber($this->orderLineItemDraftChecksumListener);
    }

    private function addFreeFormProductOnIsFreeFormPostSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        $isFreeForm = $event->getData();

        if ($isFreeForm) {
            $form
                ->getParent()
                ->add('productSku', TextType::class, ['required' => true])
                ->add('freeFormProduct', TextType::class, ['required' => true]);
        }
    }

    private function addKitItemLineItemsOnProductPostSetData(FormEvent $event): void
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

    private function replaceProductUnitOnProductPostSetData(FormEvent $event): void
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

    private function replacePriceOnProductPostSetData(FormEvent $event): void
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

    private function addFreeFormProductOnIsFreeFormPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        $isFreeForm = $form->getData();

        if ($isFreeForm) {
            $form
                ->getParent()
                ->add('productSku', TextType::class, ['required' => true])
                ->add('freeFormProduct', TextType::class, ['required' => true]);
        } else {
            $form
                ->getParent()
                ->remove('productSku')
                ->remove('freeFormProduct');
        }
    }

    private function addKitItemLineItemsOnProductPostSubmit(FormEvent $event): void
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

    private function replaceProductUnitOnProductPostSubmit(FormEvent $event): void
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

    private function replacePriceOnProductPostSubmit(FormEvent $event): void
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

    private function fillFreeFormProductOnPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var OrderLineItem $orderLineItem */
        $orderLineItem = $event->getData();

        $product = $form->get('product')->getData();
        $isFreeForm = $form->get('isFreeForm')->getData();
        if ($product !== null && $isFreeForm) {
            $orderLineItem->setIsFreeForm(true);
            $orderLineItem->setProductSku($product->getSku());
            $orderLineItem->setFreeFormProduct($product->getDenormalizedDefaultName());
        }
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var OrderLineItem|null $orderLineItem */
        $orderLineItem = $form->getData();
        $tierPrices = [];
        if ($orderLineItem instanceof OrderLineItem) {
            $tierPrices = $this->tierPricesProvider->getTierPricesForLineItem($orderLineItem);
        }

        $view->vars['tierPrices'] = $tierPrices;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', OrderLineItem::class);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_order_line_item_draft';
    }
}
