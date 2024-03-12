<?php

declare(strict_types=1);

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\ProductKit\Provider\ProductKitItemProductsProvider;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\ProductKit\Factory\RequestProductKitItemLineItemFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents an RFP request line item of a product kit item.
 */
class RequestProductKitItemLineItemType extends AbstractType
{
    private ProductKitItemProductsProvider $kitItemProductsProvider;

    private RequestProductKitItemLineItemFactory $kitItemLineItemFactory;

    public function __construct(
        ProductKitItemProductsProvider $productKitItemProductsProvider,
        RequestProductKitItemLineItemFactory $kitItemLineItemFactory
    ) {
        $this->kitItemProductsProvider = $productKitItemProductsProvider;
        $this->kitItemLineItemFactory = $kitItemLineItemFactory;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ProductKitItem $kitItem */
        $kitItem = $options['product_kit_item'];
        $choices = $this->kitItemProductsProvider->getAvailableProducts($kitItem);

        if (!$options['required']) {
            $choices[] = null;
        }

        $builder
            ->add(
                $builder
                    ->create(
                        'product',
                        ChoiceType::class,
                        [
                            'required' => $options['required'],
                            'expanded' => true,
                            'multiple' => false,
                            'choices' => $choices,
                            'choice_value' => 'id',
                            'choice_translation_domain' => false,
                            'invalid_message' => $options['required']
                                ? 'oro.rfp.requestproductkititemlineitem.product.blank.required.message'
                                : 'oro.rfp.requestproductkititemlineitem.product.blank.optional.message',
                        ]
                    )
            )
            ->add(
                'quantity',
                QuantityType::class,
                [
                    'required' => $options['required'],
                    'useInputTypeNumberValueFormat' => true,
                    'empty_data' => $kitItem?->getMinimumQuantity() ?: 1.0,
                    'limit_decimals' => false,
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        if ($event->getData() !== null) {
            return;
        }

        $form = $event->getForm();
        $formConfig = $form->getConfig();
        if (!$formConfig->getOption('set_default_data')) {
            return;
        }

        $kitItem = $formConfig->getOption('product_kit_item');
        if ($kitItem !== null) {
            $kitItemLineItem = $this->kitItemLineItemFactory->createKitItemLineItem($kitItem);
            $event->setData($kitItemLineItem);
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['product_kit_item'] = $options['product_kit_item'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('error_mapping', ['.' => 'product']);
        $resolver->setDefault('error_bubbling', false);
        $resolver->setDefault('data_class', RequestProductKitItemLineItem::class);

        $resolver->setDefault('empty_data', function (FormInterface $form) {
            /** @var ProductKitItem $kitItem */
            $kitItem = $form->getConfig()->getOption('product_kit_item');

            return $this->kitItemLineItemFactory
                ->createKitItemLineItem($kitItem)
                ->setProduct(null)
                ->setQuantity(null);
        });

        $resolver
            ->define('product_kit_item')
            ->required()
            ->allowedTypes(ProductKitItem::class);

        $resolver
            ->define('set_default_data')
            ->default(true)
            ->allowedTypes('bool');
    }

    public function getBlockPrefix(): string
    {
        return 'oro_rfp_frontend_request_product_kit_item_line_item';
    }
}
