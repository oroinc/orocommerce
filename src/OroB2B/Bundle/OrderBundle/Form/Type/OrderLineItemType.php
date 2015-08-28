<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

class OrderLineItemType extends AbstractOrderLineItemType
{
    const NAME = 'orob2b_order_line_item';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault(
            'page_component_options',
            ['view' => 'orob2border/js/app/views/line-item-view']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                'product',
                ProductSelectType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.product.entity_label',
                    'create_enabled' => false,
                ]
            )
            ->add(
                'productSku',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.product.sku.label',
                ]
            )
            ->add(
                'freeFormProduct',
                'text',
                [
                    'required' => false,
                    'label' => 'orob2b.product.entity_label',
                ]
            )
            ->add(
                'price',
                PriceType::NAME,
                [
                    'error_bubbling' => false,
                    'required' => true,
                    'label' => 'orob2b.order.orderlineitem.price.label',
                    'hide_currency' => true,
                    'default_currency' => $options['currency']
                ]
            )
            ->add(
                'priceType',
                PriceTypeSelectorType::NAME,
                [
                    'label' => 'orob2b.order.orderlineitem.price_type.label',
                    'required' => true,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
