<?php

namespace Oro\Bundle\WarehouseBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WarehouseSystemConfigType extends AbstractType
{
    const NAME = 'oro_warehouse_system_config';

    /**
     * @var string
     */
    protected $warehouseConfigClassName;

    /**
     * @param string $warehouseConfigClassName
     */
    public function __construct($warehouseConfigClassName)
    {
        $this->warehouseConfigClassName = $warehouseConfigClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => WarehouseSelectWithPriorityType::NAME,
            'options' => [
                'data_class' => $this->warehouseConfigClassName,
            ],
            'allow_add_after' => false,
            'show_form_when_empty' => true,
            'allow_add' => true,
            'mapped' => true,
            'label' => false,
            'error_bubbling' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return WarehouseCollectionType::NAME;
    }

    /**
     * {@inheritdoc}
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
        return static::NAME;
    }
}
