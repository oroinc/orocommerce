<?php

namespace Oro\Bundle\WarehouseBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Integer;

class WarehouseSelectWithPriorityType extends AbstractType
{
    const NAME = 'oro_warehouse_select_with_priority';
    const WAREHOUSE_FIELD = 'warehouse';
    const PRIORITY_FIELD = 'priority';

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                self::WAREHOUSE_FIELD,
                WarehouseSelectType::NAME,
                [
                    'empty_data' => null,
                    'required' => true,
                    'label' => 'oro.warehouse.entity_label',
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                self::PRIORITY_FIELD,
                'integer',
                [
                    'empty_data' => null,
                    'required' => true,
                    'label' => 'oro.warehouse.priority.label',
                    'constraints' => [new NotBlank(), new Integer()],
                ]
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
