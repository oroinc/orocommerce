<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\QuantityType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class FrontendLineItemType extends AbstractType
{
    const NAME = 'orob2b_shopping_list_frontend_line_item';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'unit',
                ProductUnitSelectionType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.shoppinglist.lineitem.unit.label',
                    'product_holder' => $builder->getData(),
                ]
            )
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.shoppinglist.lineitem.quantity.enter',
                    'attr' => [
                        'placeholder' => 'orob2b.shoppinglist.lineitem.quantity.placeholder',
                    ],
                    'product_holder' => $builder->getData(),
                    'product_unit_field' => 'unit',
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'validation_groups' => ['add_product'],
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

    /**
     * @param string $dataClass
     *
     * @return $this
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;

        return $this;
    }
}
