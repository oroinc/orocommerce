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
        $this->addQtyData($view, $matrixCollection);
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
                    $columnsQty = $this->getQtyByKey($columnsQty, $columnKey, (int) $column->quantity);
                    $rowsQty = $this->getQtyByKey($rowsQty, $rowKey, (int) $column->quantity);
                }
            }
        }

        $view->vars['columnsQty'] = $columnsQty;
        $view->vars['rowsQty'] = $rowsQty;
    }

    /**
     * @param array $data
     * @param int $key
     * @param int $quantity
     *
     * @return array
     */
    private function getQtyByKey(array $data, int $key, int $quantity): array
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
