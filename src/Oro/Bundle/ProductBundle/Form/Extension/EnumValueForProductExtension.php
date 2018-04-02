<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueType;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Restored this class in - #BB-9332
 */
class EnumValueForProductExtension extends AbstractTypeExtension
{
    const PRODUCT_SKU_IN_TOOLTIP = 10;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigManager $configManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return EnumValueType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $data = $form->getData();
        if (!$data || !array_key_exists('id', $data) || !$data['id']) {
            return;
        }

        /** @var FieldConfigId $configId */
        $configId = $form->getParent()->getConfig()->getOption('config_id');

        if (!$this->isValidAttributeEnum($configId)) {
            return;
        }

        $configProductsSkuUsingEnum = $this->getProductSkuUsingEnum($configId, $data['id']);

        if (count($configProductsSkuUsingEnum) > self::PRODUCT_SKU_IN_TOOLTIP) {
            $tooltipParameterMessage = sprintf(
                '%s ...',
                implode(', ', array_slice($configProductsSkuUsingEnum, 0, self::PRODUCT_SKU_IN_TOOLTIP))
            );
        } else {
            $tooltipParameterMessage = implode(', ', $configProductsSkuUsingEnum);
        }

        if ($configProductsSkuUsingEnum) {
            $view->vars['tooltip'] = 'oro.product.non_deletable_enum_value.tooltip';
            $view->vars['tooltip_parameters'] = ['%skuList%' => $tooltipParameterMessage];
            $view->vars['allow_delete'] = false;
        }
    }

    /**
     * @param FieldConfigId|null $configId
     * @return bool
     */
    private function isValidAttributeEnum($configId)
    {
        if (!$configId || !$configId instanceof FieldConfigId ||
            !is_a($configId->getClassName(), Product::class, true)
        ) {
            return false;
        }

        $attributeConfigProvider = $this->configManager->getProvider('attribute');
        $attributeConfig = $attributeConfigProvider->getConfig($configId->getClassName(), $configId->getFieldName());

        if (!$attributeConfig->is('is_attribute')) {
            return false;
        }

        return true;
    }

    /**
     * @param FieldConfigId $configId
     * @param int $enumValueId
     * @return array
     */
    private function getProductSkuUsingEnum(FieldConfigId $configId, $enumValueId)
    {
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);

        $productsUsedEnumValue = $productRepository->findByAttributeValue(
            Product::TYPE_SIMPLE,
            $configId->getFieldName(),
            $enumValueId,
            $configId->getFieldType() === EnumTypeHelper::MULTI_ENUM
        );

        $configProductsSkuUsingEnum = [];

        foreach ($productsUsedEnumValue as $simpleProduct) {
            foreach ($simpleProduct->getParentVariantLinks() as $parentVariantLink) {
                $parentProduct = $parentVariantLink->getParentProduct();

                if (in_array($configId->getFieldName(), $parentProduct->getVariantFields())) {
                    $configProductsSkuUsingEnum[$parentProduct->getSku()] = $parentProduct->getSku();
                }
            }
        }

        return $configProductsSkuUsingEnum;
    }
}
