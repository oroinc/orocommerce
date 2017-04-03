<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\ProductBundle\Form\DataTransformer\ProductVariantFieldsTransformer;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class ProductCustomVariantFieldsCollectionType extends AbstractType
{
    const NAME = 'oro_product_custom_variant_fields_collection';

    /** @var VariantFieldProvider */
    private $variantFieldProvider;

    /**
     * @param VariantFieldProvider $variantFieldProvider
     */
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
            'type' => 'oro_product_variant_field',
            'multiple' => true,
            'expanded' => true,
            'allow_add' => false,
            'allow_delete' => false,
            'handle_primary' => false
        ]);

        $resolver->setRequired(['attributeFamily']);
        $resolver->setAllowedTypes('attributeFamily', [AttributeFamily::class]);
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addModelTransformer(new ProductVariantFieldsTransformer());
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $form = $event->getForm();
        $config = $form->getConfig();
        $attributeFamily = $config->getOption('attributeFamily');

        $eventData = $event->getData();

        if (null === $eventData) {
            $eventData = [];
        }

        if (!is_array($eventData) && !($eventData instanceof \ArrayAccess)) {
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
        return 'oro_collection';
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
