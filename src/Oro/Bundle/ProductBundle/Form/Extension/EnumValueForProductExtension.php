<?php

namespace Oro\Bundle\ProductBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueType;

class EnumValueForProductExtension extends AbstractTypeExtension
{
    const PRODUCT_SKU_IN_TOOLTIP = 10;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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
        if (!$data) {
            return;
        }

        /** @var FieldConfigId $configId */
        $configId = $form->getParent()->getConfig()->getOption('config_id');

        if (!$configId || !$configId instanceof FieldConfigId ||
            !is_a($configId->getClassName(), Product::class, true)
        ) {
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
     * @param FieldConfigId $configId
     * @param int $enumValueId
     * @return array
     */
    private function getProductSkuUsingEnum(FieldConfigId $configId, $enumValueId)
    {
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);

        /** @var Product[] $productsUsedEnumValue */
        $productsUsedEnumValue = $productRepository->findBy([
            'type' => Product::TYPE_SIMPLE,
            $configId->getFieldName() => $enumValueId
        ]);

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
