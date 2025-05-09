<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\FormBundle\Utils\FormUtils;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemProductsProvider;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents a product kit item line item in request product line item.
 */
class RequestProductKitItemLineItemType extends AbstractType
{
    private ProductKitItemProductsProvider $kitItemProductsProvider;

    private EventSubscriberInterface $kitItemLineItemDefaultDataListener;

    private EventSubscriberInterface $kitItemLineItemGhostOptionListener;

    public function __construct(
        ProductKitItemProductsProvider $productKitItemProductsProvider,
        EventSubscriberInterface $kitItemLineItemDefaultDataListener,
        EventSubscriberInterface $kitItemLineItemGhostOptionListener
    ) {
        $this->kitItemProductsProvider = $productKitItemProductsProvider;
        $this->kitItemLineItemDefaultDataListener = $kitItemLineItemDefaultDataListener;
        $this->kitItemLineItemGhostOptionListener = $kitItemLineItemGhostOptionListener;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        /** @var ProductKitItem|null $kitItem */
        $kitItem = $options['product_kit_item'];
        if ($kitItem !== null) {
            $choices = $this->kitItemProductsProvider->getAvailableProducts($kitItem);
        }

        $emptyDataChoice = null;
        if ($choices) {
            $emptyDataChoice = (string)reset($choices)->getId();
        }

        $builder
            ->add(
                $builder
                    ->create(
                        'product',
                        Select2EntityType::class,
                        [
                            'choice_loader' => null,
                            'required' => $options['required'],
                            'expanded' => false,
                            'multiple' => false,
                            'choices' => $choices,
                            'choice_value' => 'id',
                            'class' => Product::class,
                            'placeholder' => !$options['required']
                                ? 'oro.rfp.requestproductkititemlineitem.product.form.choices.none'
                                : false,
                            'choice_label' => function (?Product $product) {
                                return $product?->getSku() . ' - ' . $product?->getDefaultName();
                            },
                            'choice_translation_domain' => false,
                            'empty_data' => $options['required'] ? $emptyDataChoice : null,
                        ]
                    )
                    ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'disableQuantity'])
                    ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'disableQuantity'])
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'required' => $options['required'],
                    'useInputTypeNumberValueFormat' => true,
                    'empty_data' => $kitItem?->getMinimumQuantity() ?: 1.0,
                ]
            );

        $builder->addEventSubscriber($this->kitItemLineItemDefaultDataListener);
        $builder->addEventSubscriber($this->kitItemLineItemGhostOptionListener);
    }

    public function disableQuantity(FormEvent $event): void
    {
        $form = $event->getForm();
        $isDisabled = $form->getData() === null;
        /** @var FormInterface $parentForm */
        $parentForm = $form->getParent();

        $options = ['disabled' => $isDisabled];
        if ($isDisabled) {
            $options['data'] = null;
        }

        FormUtils::replaceField($parentForm, 'quantity', $options);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['product_kit_item'] = $options['product_kit_item'];

        /** @var RequestProductKitItemLineItem|null $kitItemLineItem */
        $kitItemLineItem = $form->getData();

        if ($kitItemLineItem !== null) {
            $view->vars['label'] = $kitItemLineItem->getKitItemLabel();
            $view->vars['is_optional'] = $kitItemLineItem->isOptional();
            $view->vars['minimum_quantity'] = $kitItemLineItem->getMinimumQuantity();
            $view->vars['maximum_quantity'] = $kitItemLineItem->getMaximumQuantity();
            $view->vars['unit_code'] = $kitItemLineItem->getProductUnitCode();
            $view->vars['unit_precision'] = $kitItemLineItem->getProductUnitPrecision();
        } else {
            $view->vars['label'] = null;
            $view->vars['is_optional'] = null;
            $view->vars['minimum_quantity'] = null;
            $view->vars['maximum_quantity'] = null;
            $view->vars['unit_code'] = null;
            $view->vars['unit_precision'] = null;
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('error_mapping', ['.' => 'product']);
        $resolver->setDefault('error_bubbling', false);
        $resolver->setDefault('data_class', RequestProductKitItemLineItem::class);

        $resolver
            ->define('product_kit_item')
            ->default(null)
            ->allowedTypes(ProductKitItem::class, 'null');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_rfp_request_product_kit_item_line_item';
    }
}
