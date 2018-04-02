<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatrixRowType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('columns', CollectionType::class, [
            'entry_type' => MatrixColumnType::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var MatrixCollectionRow $row */
        $row = $form->getData();
        if ($row instanceof MatrixCollectionRow) {
            $view->vars['label'] = $row->label;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MatrixCollectionRow::class,
        ]);
    }
}
