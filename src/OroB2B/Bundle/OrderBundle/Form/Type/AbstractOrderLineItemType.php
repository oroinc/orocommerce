<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

abstract class AbstractOrderLineItemType extends AbstractType
{
    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
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
                'quantity',
                'integer',
                [
                    'required' => true,
                    'label' => 'orob2b.order.orderlineitem.quantity.label',
                ]
            )
            ->add(
                'productUnit',
                ProductUnitSelectionType::NAME,
                [
                    'label' => 'orob2b.product.productunit.entity_label',
                    'required' => true,
                ]
            )
            ->add(
                'shipBy',
                'oro_date',
                [
                    'required' => false,
                    'label' => 'orob2b.order.orderlineitem.ship_by.label',
                ]
            )
            ->add(
                'comment',
                'textarea',
                [
                    'required' => false,
                    'label' => 'orob2b.order.orderlineitem.comment.label',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['currency']);
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'intention' => 'order_line_item',
                'currency' => null
            ]
        );
    }
}
