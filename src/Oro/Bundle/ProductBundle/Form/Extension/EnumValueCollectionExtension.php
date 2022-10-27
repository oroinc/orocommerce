<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueCollectionType;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Extends EnumValueCollectionType children with allow_delete flag depending on configurable products related to options
 * If any configurable product has simple products with certain option assigned such option can not be removed
 * and we show notification for that
 */
class EnumValueCollectionExtension extends AbstractTypeExtension
{
    private const PRODUCT_SKU_IN_TOOLTIP = 10;

    public function __construct(private DoctrineHelper $doctrineHelper, private ConfigManager $configManager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [EnumValueCollectionType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $data = $form->getData();
        if (!$data || !\is_array($data)) {
            return;
        }

        /** @var FieldConfigId $configId */
        $configId = $form->getConfig()->getOption('config_id');

        if (!$this->isValidAttributeEnum($configId)) {
            return;
        }

        $attributeOptionIds = [];

        foreach ($data as $option) {
            //Empty option id means option is not saved so no need further logic
            if (null === $option['id'] || '' === $option['id']) {
                continue;
            }
            $attributeOptionIds[] = $option['id'];
        }

        if (!$attributeOptionIds) {
            return;
        }

        $optionsWithProductsSkusAssigned = $this->getProductSkuUsingEnum($configId, $attributeOptionIds);

        $this->updateChildrenViews($view, $optionsWithProductsSkusAssigned);
    }

    private function updateChildrenViews(FormView $formView, array $optionsWithProductsSkusAssigned)
    {
        foreach ($formView->children as $childView) {
            $optionId = $childView->vars['value']['id'];

            if (isset($optionsWithProductsSkusAssigned[$optionId])) {
                $configurableProductSkus = $optionsWithProductsSkusAssigned[$optionId];

                if (count($configurableProductSkus) > self::PRODUCT_SKU_IN_TOOLTIP) {
                    $slicedSkus = array_slice($configurableProductSkus, 0, self::PRODUCT_SKU_IN_TOOLTIP);
                    $tooltipParameterMessage = sprintf(
                        '%s ...',
                        implode(', ', $slicedSkus)
                    );
                } else {
                    $tooltipParameterMessage = implode(', ', $configurableProductSkus);
                }

                if ($configurableProductSkus) {
                    $childView->vars['tooltip'] = 'oro.product.non_deletable_enum_value.tooltip';
                    $childView->vars['tooltip_parameters'] = ['%skuList%' => $tooltipParameterMessage];
                    $childView->vars['allow_delete'] = false;
                }
            }
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
     * @param array $attributeOptionIds
     * @return array
     */
    private function getProductSkuUsingEnum(FieldConfigId $configId, array $attributeOptionIds)
    {
        if ($configId->getFieldType() !== EnumTypeHelper::TYPE_ENUM) {
            return [];
        }

        /** @var ProductRepository $productRepository */
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);

        $configProductsSkuUsingEnum = $productRepository->findParentSkusByAttributeOptions(
            Product::TYPE_SIMPLE,
            $configId->getFieldName(),
            $attributeOptionIds
        );

        return $configProductsSkuUsingEnum;
    }
}
