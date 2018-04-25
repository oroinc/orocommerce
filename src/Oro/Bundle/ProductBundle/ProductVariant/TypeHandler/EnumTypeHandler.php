<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\TypeHandler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerInterface;
use Symfony\Component\Form\FormFactory;

/**
 * Provides Enum form type instance for variant selector of configurable product.
 */
class EnumTypeHandler implements ProductVariantTypeHandlerInterface
{
    const TYPE = 'enum';

    /** @var FormFactory */
    protected $formFactory;

    /** @var string */
    protected $productClass;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param FormFactory $formFactory
     * @param string $productClass
     * @param ConfigManager $configManager
     */
    public function __construct(FormFactory $formFactory, $productClass, ConfigManager $configManager)
    {
        $this->formFactory = $formFactory;
        $this->productClass = $productClass;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function createForm($fieldName, array $availability, array $options = [])
    {
        $options = array_merge($this->getOptions($fieldName, $availability), $options);
        $form = $this->formFactory->createNamed($fieldName, EnumSelectType::class, null, $options);

        return $form;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @param string $fieldName
     * @param array $availability
     * @return array
     */
    private function getOptions($fieldName, array $availability)
    {
        $notAvailableVariants = array_filter($availability, function ($item) {
            return $item === false;
        });

        $disabledValues = array_map('\strval', array_keys($notAvailableVariants));

        $config = $this->configManager->getConfigFieldModel($this->productClass, $fieldName);
        $extendConfig = $config->toArray('extend');

        return [
            'class' => $extendConfig['target_entity'],
            'configs' => ['allowClear' => false],
            'disabled_values' => $disabledValues,
            'auto_initialize' => false
        ];
    }
}
