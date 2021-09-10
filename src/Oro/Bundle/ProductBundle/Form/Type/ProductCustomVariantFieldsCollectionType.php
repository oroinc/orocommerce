<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductVariantFieldsTransformer;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductCustomVariantFieldsCollectionType extends AbstractType
{
    const NAME = 'oro_product_custom_variant_fields_collection';

    /** @var VariantFieldProvider */
    private $variantFieldProvider;

    public function __construct(VariantFieldProvider $variantFieldProvider)
    {
        $this->variantFieldProvider = $variantFieldProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => ProductVariantFieldType::class,
            'multiple' => true,
            'expanded' => true,
            'allow_add' => false,
            'allow_delete' => false,
            'handle_primary' => false,
            'attributeFamily' => null
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addModelTransformer(new ProductVariantFieldsTransformer());
    }

    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $config = $form->getConfig();
        $attributeFamily = $config->getOption('attributeFamily');

        if (!$attributeFamily) {
            return;
        }

        $eventData = $event->getData();

        if (!$eventData) {
            $eventData = [];
        } elseif (!is_array($eventData) && !($eventData instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($eventData, 'array or \ArrayAccess');
        }

        foreach ($form as $name => $child) {
            $form->remove($name);
        }

        $variantFields = $this->variantFieldProvider->getVariantFields($attributeFamily);

        $data = [];
        $fieldsToAdd = [];
        foreach ($variantFields as $field) {
            $fieldName = $field->getName();
            $priority = array_search($fieldName, $eventData);
            $selected = $priority !== false;

            $priority = $selected ? $priority : 9999;
            $data[$fieldName] = [
                'priority' => $priority,
                'is_selected' => $selected,
            ];

            $fieldsToAdd[$field->getName()] = [
                'name' => $fieldName,
                'priority' => $priority,
                'label' => $field->getLabel(),
            ];
        }

        uasort($fieldsToAdd, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        foreach ($fieldsToAdd as $field) {
            $form->add($field['name'], $form->getConfig()->getOption('entry_type'), array_replace([
                'property_path' => '['.$field['name'].']',
                'label' => $field['label'],
            ], $form->getConfig()->getOption('entry_options')));
        }

        $event->setData($data);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * @return string
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
