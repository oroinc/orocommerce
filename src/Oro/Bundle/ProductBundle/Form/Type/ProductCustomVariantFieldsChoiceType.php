<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

class ProductCustomVariantFieldsChoiceType extends AbstractType
{
    const NAME = 'oro_product_custom_variant_fields_choice';

    /**
     * @var CustomFieldProvider
     */
    private $customFieldProvider;

    /**
     * @var string
     */
    private $productClass;

    /**
     * @param CustomFieldProvider $customFieldProvider
     * @param $productClass
     */
    public function __construct(CustomFieldProvider $customFieldProvider, $productClass)
    {
        $this->customFieldProvider = $customFieldProvider;
        $this->productClass = $productClass;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices'              => $this->getCustomVariantFields(),
            'multiple'             => true,
            'expanded'             => true,
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
        ]);
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return 'choice';
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

    /**
     * @return array
     */
    protected function getCustomVariantFields()
    {
        $result = [];
        $customFields = $this->customFieldProvider->getEntityCustomFields($this->productClass);

        // Show only boolean and enum as allowed
        $customVariantFields = array_filter($customFields, function ($field) {
            return in_array($field['type'], ['boolean', 'enum'], true);
        });

        // Skip serialized fields. Should be improved in BB-6526
        $customVariantFields = array_filter($customVariantFields, function ($field) {
            return !$field['is_serialized'];
        });

        foreach ($customVariantFields as $field) {
            $result[$field['name']] = $field['label'];
        }

        return $result;
    }
}
