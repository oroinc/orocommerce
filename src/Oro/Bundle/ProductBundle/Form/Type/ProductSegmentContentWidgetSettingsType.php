<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

/**
 * Form type for the settings of Product Segment content widget.
 */
class ProductSegmentContentWidgetSettingsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'segment',
            SegmentChoiceType::class,
            [
                'label' => 'oro.product.content_widget_type.product_segment.options.segment.label',
                'tooltip' => 'oro.product.content_widget_type.product_segment.options.segment.tooltip',
                'required' => true,
                'entityClass' => Product::class,
                'block' => 'options',
                'block_config' => [
                    'options' => [
                        'title' => 'oro.product.sections.options'
                    ]
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ]
        );

        $builder->add(
            'maximum_items',
            IntegerType::class,
            [
                'label' => 'oro.product.content_widget_type.product_segment.options.maximum_items.label',
                'tooltip' => 'oro.product.content_widget_type.product_segment.options.maximum_items.tooltip',
                'required' => true,
                'block' => 'options',
                'constraints' => [
                    new NotBlank(),
                    new Type('integer'),
                    new Range(['min' => 1]),
                ]
            ]
        );

        $builder->add(
            'minimum_items',
            IntegerType::class,
            [
                'label' => 'oro.product.content_widget_type.product_segment.options.minimum_items.label',
                'tooltip' => 'oro.product.content_widget_type.product_segment.options.minimum_items.tooltip',
                'required' => true,
                'block' => 'options',
                'constraints' => [
                    new NotBlank(),
                    new Type('integer'),
                    new Range(['min' => 1]),
                ]
            ]
        );

        $builder->add(
            'use_slider_on_mobile',
            ChoiceType::class,
            [
                'label' => 'oro.product.content_widget_type.product_segment.options.use_slider_on_mobile.label',
                'tooltip' => 'oro.product.content_widget_type.product_segment.options.use_slider_on_mobile.tooltip',
                'required' => true,
                'placeholder' => false,
                'block' => 'options',
                'constraints' => [
                    new Type('boolean'),
                ],
                'choices' => [
                    'oro.product.content_widget_type.product_segment.options.use_slider_on_mobile.value.no' => false,
                    'oro.product.content_widget_type.product_segment.options.use_slider_on_mobile.value.yes' => true,
                ]
            ]
        );

        $builder->add(
            'show_add_button',
            ChoiceType::class,
            [
                'label' => 'oro.product.content_widget_type.product_segment.options.show_add_button.label',
                'tooltip' => 'oro.product.content_widget_type.product_segment.options.show_add_button.tooltip',
                'required' => true,
                'placeholder' => false,
                'block' => 'options',
                'constraints' => [
                    new Type('boolean'),
                ],
                'choices' => [
                    'oro.product.content_widget_type.product_segment.options.show_add_button.value.no' => false,
                    'oro.product.content_widget_type.product_segment.options.show_add_button.value.yes' => true,
                ]
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $data = $event->getData();
        if (!is_array($data)) {
            $data  = [];
        }

        if (!isset($data['maximum_items'])) {
            $data['maximum_items'] = 4;
        }

        if (!isset($data['minimum_items'])) {
            $data['minimum_items'] = 3;
        }

        if (!array_key_exists('use_slider_on_mobile', $data)) {
            $data['use_slider_on_mobile'] = false;
        }

        if (!array_key_exists('show_add_button', $data)) {
            $data['show_add_button'] = true;
        }

        $event->setData($data);
    }
}
