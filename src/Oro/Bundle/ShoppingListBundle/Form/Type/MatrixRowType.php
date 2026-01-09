<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for a single row in the matrix order form for configurable products.
 *
 * This form type represents one row in the matrix grid used for ordering configurable products with multiple variants.
 * Each row corresponds to one value of the first variant field (e.g., "Size") and contains a collection of columns
 * representing the second variant field values (e.g., "Color").
 * The form allows customers to specify quantities for multiple product variants simultaneously in a grid layout,
 * providing an efficient way to add multiple variants to a shopping list.
 */
class MatrixRowType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('columns', CollectionType::class, [
            'entry_type' => MatrixColumnType::class,
        ]);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var MatrixCollectionRow $row */
        $row = $form->getData();
        if ($row instanceof MatrixCollectionRow) {
            $view->vars['label'] = $row->label;
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MatrixCollectionRow::class,
        ]);
    }
}
