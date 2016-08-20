<?php

namespace Oro\Bundle\WarehouseBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class OrderFormExtension extends AbstractTypeExtension
{
    /**
     * @var WarehouseCounter
     */
    private $warehouseCounter;

    /**
     * OrderFormExtension constructor.
     *
     * @param WarehouseCounter $warehouseCounter
     */
    public function __construct(WarehouseCounter $warehouseCounter)
    {
        $this->warehouseCounter = $warehouseCounter;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->warehouseCounter->areMoreWarehouses()) {
            $builder->add('warehouse', 'entity', [
                'label' => 'oro.warehouse.form.order.label',
                'class' => Warehouse::class,
                'required' => false,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->children['warehouse']->vars['extra_field'] = false;
    }
}
