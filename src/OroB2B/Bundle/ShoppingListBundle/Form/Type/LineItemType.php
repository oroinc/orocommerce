<?php

namespace OroB2B\Bundle\ShoppingListBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\QuantityType;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

class LineItemType extends AbstractType
{
    const NAME = 'orob2b_shopping_list_line_item';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var LineItem $data */
        $data = $builder->getData();
        $isExisting = $data && $data->getId();

        $unitOptions = [
            'required' => true,
            'label' => 'orob2b.shoppinglist.lineitem.unit.label',
        ];

        $builder
            ->add(
                'product',
                ProductSelectType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.shoppinglist.lineitem.product.label',
                    'create_enabled' => false,
                    'disabled' => $isExisting,
                ]
            )
            ->add('unit', ProductUnitSelectionType::NAME, $unitOptions)
            ->add(
                'quantity',
                QuantityType::NAME,
                [
                    'required' => true,
                    'label' => 'orob2b.shoppinglist.lineitem.quantity.label',
                    'product' => $data ? $data->getProduct() : null,
                    'product_unit_field' => 'unit',
                ]
            )
            ->add(
                'notes',
                'textarea',
                [
                    'required' => false,
                    'label' => 'orob2b.shoppinglist.lineitem.notes.label',
                    'empty_data' => null,
                ]
            );

        $builder
            ->get('product')
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function (FormEvent $event) use ($unitOptions) {
                    $productId = $event->getData();

                    $this->replaceUnitField($event->getForm(), $unitOptions, $productId);
                }
            );
    }

    /**
     * @param FormInterface $form
     * @param $unitOptions
     * @param $productId
     */
    protected function replaceUnitField(FormInterface $form, $unitOptions, $productId)
    {
        if ($productId) {
            $unitOptions['query_builder'] = function (ProductUnitRepository $er) use ($productId) {
                return $er->getProductUnitsQueryBuilderById($productId);
            };
        } else {
            $unitOptions['choices'] = [];
        }

        $form->getParent()
            ->add(
                'unit',
                ProductUnitSelectionType::NAME,
                array_merge(
                    $unitOptions,
                    [
                        'query_builder' => function (ProductUnitRepository $er) use ($productId) {
                            return $er->getProductUnitsQueryBuilderById($productId);
                        },
                    ]
                )
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'validation_groups' => function (FormInterface $form) {
                    return $form->getData()->getId() ? ['update'] : ['create'];
                },
            ]
        );
    }
}
