<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Provider\CustomFieldProvider;

class ProductCustomFieldsChoiceType extends AbstractType
{
    const NAME = 'orob2b_product_custom_entity_fields_choice';

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
        return self::NAME;
    }

    /**
     * @return array
     */
    private function getProductCustomFields()
    {
        $result = [];
        $customFields = $this->customFieldProvider->getEntityCustomFields($this->productClass);

        foreach ($customFields as $field) {
            $result[$field['name']] = $field['label'];
        }

        return $result;
    }
}
