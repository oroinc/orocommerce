<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;

class MatrixColumnType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var MatrixCollectionColumn $column */
            $column = $event->getData();

            $quantityConfig = [
                'label' => false,
                'attr' => [
                    'placeholder' => 'oro.frontend.shoppinglist.view.qty.label'
                ],
            ];
            if ($column->product === null) {
                $quantityConfig['disabled'] = true;
            }
            $event->getForm()->add('quantity', NumberType::class, $quantityConfig);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var MatrixCollectionColumn $column */
        $column = $form->getData();
        if ($column instanceof MatrixCollectionColumn) {
            $view->vars['label'] = $column->label;
            $view->vars['productId'] = $column->product ? $column->product->getId() : null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MatrixCollectionColumn::class,
        ]);
    }
}
