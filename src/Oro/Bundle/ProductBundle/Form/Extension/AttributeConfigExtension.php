<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;

class AttributeConfigExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $configModel = $options['config_model'];
        if ($configModel instanceof FieldConfigModel) {
            $className = $configModel->getEntity()->getClassName();
            if ($className === Product::class) {
                $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
            }
        }
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $options = $event->getForm()->getConfig()->getOptions();

        /** @var FieldConfigModel $configModel */
        $configModel = $options['config_model'];
        $data = $event->getData();

        $support = $data['attribute']['is_attribute'] && null === $configModel->getId();
        if ($support && $this->hasDefaultValue($data, 'datagrid', 'is_visible')) {
            $data['datagrid']['is_visible'] = DatagridScope::IS_VISIBLE_HIDDEN;
        }

        $event->setData($data);
    }

    /**
     * @param array $data
     * @param string $scope
     * @param string $fieldName
     *
     * @return bool
     */
    private function hasDefaultValue(array $data, $scope, $fieldName)
    {
        return array_key_exists($scope, $data) && array_key_exists($fieldName, $data[$scope]);
    }


    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'oro_entity_config_type';
    }
}
