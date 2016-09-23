<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

class ProductCustomFieldsChoiceType extends AbstractType
{
    const NAME = 'oro_product_custom_entity_fields_choice';

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
            'choices'              => $this->getProductCustomFields(),
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
        return self::NAME;
    }

    /**
     * @return array
     */
    protected function getProductCustomFields()
    {
        $result = [];
        $customFields = $this->customFieldProvider->getEntityCustomFields($this->productClass);

        foreach ($customFields as $field) {
            $result[$field['name']] = $field['label'];
        }

        return $result;
    }
}
