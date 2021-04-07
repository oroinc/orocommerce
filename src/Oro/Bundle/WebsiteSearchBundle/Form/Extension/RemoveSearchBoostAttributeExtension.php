<?php

namespace Oro\Bundle\WebsiteSearchBundle\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Extension\AttributeConfigExtensionApplicableTrait;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Removes boosting config from the form if orm search engine is used or attribute have not searchable type.
 */
class RemoveSearchBoostAttributeExtension extends AbstractTypeExtension
{
    use AttributeConfigExtensionApplicableTrait;

    /** @var string */
    private $websiteSearchEngine;

    /** @var AttributeTypeRegistry */
    private $attributeTypeRegistry;

    /**
     * @param string                $websiteSearchEngine
     * @param ConfigProvider        $attributeConfigProvider
     * @param AttributeTypeRegistry $attributeTypeRegistry
     */
    public function __construct(
        $websiteSearchEngine,
        ConfigProvider $attributeConfigProvider,
        AttributeTypeRegistry $attributeTypeRegistry
    ) {
        $this->websiteSearchEngine     = $websiteSearchEngine;
        $this->attributeConfigProvider = $attributeConfigProvider;
        $this->attributeTypeRegistry   = $attributeTypeRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $configModel = $options['config_model'];
        if ($configModel instanceof FieldConfigModel) {
            if ($this->isApplicable($configModel) && $builder->has('attribute')) {
                $attribute = $builder->get('attribute');
                if ($this->websiteSearchEngine !== 'elastic_search') {
                    $attribute->remove('search_boost');

                    return;
                }

                $attributeType = $this->attributeTypeRegistry->getAttributeType($configModel);
                if (!$attributeType || !$attributeType->isSearchable($configModel)) {
                    $attribute->remove('search_boost');

                    return;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [ConfigType::class];
    }
}
