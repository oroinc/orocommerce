<?php

namespace OroB2B\Bundle\WarehouseBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use OroB2B\Bundle\OrderBundle\Form\Section\SectionProvider;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use OroB2B\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;
use OroB2B\Bundle\WarehouseBundle\Entity\Warehouse;

class OrderLineItemFormExtension extends AbstractTypeExtension
{
    /**
     * @var SectionProvider
     */
    protected $sectionProvider;

    /**
     * @var WarehouseCounter
     */
    protected $warehouseCounter;

    /**
     * OrderLineItemFormExtension constructor.
     *
     * @param SectionProvider $sectionProvider
     * @param WarehouseCounter $warehouseCounter
     */
    public function __construct(SectionProvider $sectionProvider, WarehouseCounter $warehouseCounter)
    {
        $this->sectionProvider = $sectionProvider;
        $this->warehouseCounter = $warehouseCounter;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderLineItemType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($this->warehouseCounter->areMoreWarehouses()) {
            $builder->add('warehouse', 'entity', [
                'class' => Warehouse::class,
                'label' => 'orob2b.warehouse.form.order.label',
                'required' => false
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($this->warehouseCounter->areMoreWarehouses()) {
            $this->sectionProvider->addSections(
                OrderLineItemType::NAME,
                [
                    'warehouse' => [
                        'data' => ['warehouse' => []],
                        'order' => 11,
                        'label' => 'orob2b.warehouse.form.order.label'
                    ],
                ]
            );
        }
    }
}
