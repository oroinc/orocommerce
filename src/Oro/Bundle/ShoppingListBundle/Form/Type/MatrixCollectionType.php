<?php

namespace Oro\Bundle\ShoppingListBundle\Form\Type;

use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Allows to edit quantity of all product variants combined to matrix form.
 */
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
        $this->addQtyData($view, $matrixCollection);
        $view->vars['columns'] = $matrixCollection?->columns;
        $view->vars['dimensions'] = $matrixCollection?->dimensions;
    }

    /**
     * @param FormView $view
     * @param MatrixCollection $matrixCollection
     */
    private function addQtyData(FormView $view, ?MatrixCollection $matrixCollection): void
    {
        $columnsQty = [];
        $rowsQty = [];

        if ($matrixCollection) {
            foreach ($matrixCollection->rows as $rowKey => $row) {
                foreach ($row->columns as $columnKey => $column) {
                    $columnsQty = $this->getQtyByKey($columnsQty, $columnKey, (float) $column->quantity);
                    $rowsQty = $this->getQtyByKey($rowsQty, $rowKey, (float) $column->quantity);
                }
            }
        }

        $view->vars['columnsQty'] = $columnsQty;
        $view->vars['rowsQty'] = $rowsQty;
    }

    private function getQtyByKey(array $data, int $key, float $quantity): array
    {
        if (isset($data[$key])) {
            $data[$key] += $quantity;
        } else {
            $data[$key] = $quantity ?: 0;
        }

        return $data;
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
