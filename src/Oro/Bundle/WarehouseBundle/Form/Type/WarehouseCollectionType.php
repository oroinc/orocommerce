<?php

namespace Oro\Bundle\WarehouseBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\WarehouseBundle\SystemConfig\WarehouseConfig;
use Oro\Bundle\WarehouseBundle\Validator\Constraints\UniqueWarehouse;

class WarehouseCollectionType extends AbstractType
{
    const NAME = 'oro_warehouse_collection';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'website' => null,
                'type' => WarehouseSelectWithPriorityType::NAME,
                'mapped' => false,
                'label' => false,
                'handle_primary' => false,
                'constraints' => [new UniqueWarehouse()],
                'required' => false,
                'render_as_widget' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['render_as_widget'] = $options['render_as_widget'];
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
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
        return self::NAME;
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $data = [];
        $submitted = $event->getData() ?: [];
        foreach ($submitted as $index => $item) {
            if ($this->isEmpty($item)) {
                $event->getForm()->remove($index);
            } else {
                $data[$index] = $item;
            }
        }

//        $data = $this->reorderData($data, $event->getForm());

        $event->setData($data);
    }

    /**
     * @param WarehouseConfig|array $item
     * @return bool
     */
    protected function isEmpty($item)
    {
        return is_array($item)
        && !$item[WarehouseSelectWithPriorityType::WAREHOUSE_FIELD]
        && !$item[WarehouseSelectWithPriorityType::PRIORITY_FIELD];
    }
}
