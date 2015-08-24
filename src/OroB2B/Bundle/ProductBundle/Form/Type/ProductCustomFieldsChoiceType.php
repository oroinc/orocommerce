<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class ProductCustomFieldsChoiceType extends AbstractType
{
    const NAME = 'orob2b_product_custom_entity_fields_choice';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var string
     */
    private $productClass;

    /**
     * @param ConfigManager $configManager
     * @param $productClass
     */
    public function __construct(ConfigManager $configManager, $productClass)
    {
        $this->configManager = $configManager;
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
        $extendConfig = $this->configManager->getProvider('extend')->getConfig($this->productClass);
        $schema = $extendConfig->get('schema');
        $customProperties = $schema['property'];
        unset($customProperties['serialized_data']);

        return $customProperties;
    }
}
