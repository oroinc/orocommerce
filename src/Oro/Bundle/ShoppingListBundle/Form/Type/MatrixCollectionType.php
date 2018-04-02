<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MatrixCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('rows', CollectionType::class, [
            'entry_type' => MatrixRowType::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        /** @var MatrixCollection $matrixCollection */
        $matrixCollection = $form->getData();
        $view->vars['columnsQty'] = $matrixCollection ? $this->getColumnsQty($matrixCollection) : [];
    }

    /**
     * Return the summary qty amount by column
     *
     * @param MatrixCollection $matrixCollection
     *
     * @return array
     */
    private function getColumnsQty(MatrixCollection $matrixCollection)
    {
        $columnsQty = [];
        foreach ($matrixCollection->rows as $row) {
            foreach ($row->columns as $key => $column) {
                if (isset($columnsQty[$key])) {
                    $columnsQty[$key] += $column->quantity;
                } else {
                    $columnsQty[$key] = $column->quantity ? $column->quantity : 0;
                }
            }
        }

        return $columnsQty;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => MatrixCollection::class,
        ]);
    }
}
