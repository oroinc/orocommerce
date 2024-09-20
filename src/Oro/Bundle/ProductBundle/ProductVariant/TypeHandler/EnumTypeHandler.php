<?php

namespace Oro\Bundle\ProductBundle\ProductVariant\TypeHandler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerInterface;
use Symfony\Component\Form\FormFactory;

/**
 * Provides Enum form type instance for variant selector of configurable product.
 */
class EnumTypeHandler implements ProductVariantTypeHandlerInterface
{
    const TYPE = 'enum';

    public function __construct(
        protected FormFactory $formFactory,
        protected string $productClass,
        protected ConfigManager $configManager
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function createForm($fieldName, array $availability, array $options = [])
    {
        $options = array_merge($this->getOptions($fieldName, $availability), $options);

        return $this->formFactory->createNamed($fieldName, EnumSelectType::class, null, $options);
    }

    public function getType(): string
    {
        return self::TYPE;
    }

    private function getOptions(string $fieldName, array $availability): array
    {
        $notAvailableVariants = array_filter($availability, function ($item) {
            return $item === false;
        });
        $disabledValues = array_map('\strval', array_keys($notAvailableVariants));
        $config = $this->configManager->getConfigFieldModel($this->productClass, $fieldName);
        $enumConfig = $config->toArray('enum');

        return [
            'class' => EnumOption::class,
            'enum_code' => $enumConfig['enum_code'],
            'configs' => ['allowClear' => false],
            'disabled_values' => $disabledValues,
            'auto_initialize' => false,
            'multiple' => ExtendHelper::isMultiEnumType($config->getType())
        ];
    }
}
