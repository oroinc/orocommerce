<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ConfigModelAwareConstraintInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that select attribute value are not removed if were used as product variant values.
 */
class AttributeValueUsageInVariantValidator extends ConstraintValidator
{
    public const ALIAS = 'oro_product_attribute_value_usage_in_variant_field';
    private const MAX_PRODUCT_SKU_IN_MESSAGE = 10;


    public function __construct(
        private ManagerRegistry $registry,
        private EnumSynchronizer $enumSynchronizer
    ) {
    }

    /**
     * @param array $value
     * @param Constraint|AttributeValueUsageInVariant|ConfigModelAwareConstraintInterface $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value)) {
            return;
        }

        if (!$this->isSupportedConstraint($constraint)) {
            return;
        }

        $persistedOptions = $this->getPersistedOptions($constraint);
        $this->enumSynchronizer->fillOptionIds($persistedOptions, $value);
        $removedIds = $this->getRemovedIds($persistedOptions, $value);
        if (!$removedIds) {
            return;
        }

        $configProductsSkuUsingEnum = $this->getProductVariantsByOptions(
            $constraint->getConfigModel()->getFieldName(),
            $removedIds
        );
        if (!$configProductsSkuUsingEnum) {
            return;
        }

        $usedEnumLabels = $this->getUsedEnumLabels($persistedOptions, $configProductsSkuUsingEnum);
        $productSkus = $this->getProductSkusFoErrorMessage($configProductsSkuUsingEnum);

        $this->context->addViolation(
            $constraint->message,
            [
                '%productSkus%' => $productSkus,
                '%optionLabels%' => implode(', ', $usedEnumLabels)
            ]
        );
    }

    private function getTargetEntity(ConfigModelAwareConstraintInterface $constraint): ?string
    {
        $configModel = $constraint->getConfigModel();
        $extendConfig = $configModel->toArray('extend');

        return $extendConfig['target_entity'] ?? null;
    }

    private function isSupportedConstraint(Constraint $constraint): bool
    {
        if (!$constraint instanceof ConfigModelAwareConstraintInterface) {
            return false;
        }

        $configModel = $constraint->getConfigModel();
        if (!$configModel instanceof FieldConfigModel) {
            return false;
        }

        if ($configModel->getEntity()->getClassName() !== Product::class) {
            return false;
        }

        $targetEntity = $this->getTargetEntity($constraint);

        if (!$targetEntity || !is_a($targetEntity, AbstractEnumValue::class, true)) {
            return false;
        }

        return true;
    }

    private function getUsedEnumLabels(array $persistedValues, array $configProductsSkuUsingEnum): array
    {
        $usedEnumLabels = [];
        foreach ($persistedValues as $persistedValue) {
            if (array_key_exists($persistedValue->getId(), $configProductsSkuUsingEnum)) {
                $usedEnumLabels[] = $persistedValue->getName();
            }
        }

        return $usedEnumLabels;
    }

    private function getProductSkusFoErrorMessage(array $configProductsSkuUsingEnum): string
    {
        $skus = array_merge(...array_values($configProductsSkuUsingEnum));
        if (count($skus) > self::MAX_PRODUCT_SKU_IN_MESSAGE) {
            $slicedSkus = array_slice($skus, 0, self::MAX_PRODUCT_SKU_IN_MESSAGE);
            $productSkus = sprintf(
                '%s ...',
                implode(', ', $slicedSkus)
            );
        } else {
            $productSkus = implode(', ', $skus);
        }

        return $productSkus;
    }

    private function getPersistedOptions($constraint): array
    {
        $targetEntity = $this->getTargetEntity($constraint);
        $repo = $this->registry->getRepository($targetEntity);

        return $repo->findAll();
    }

    private function getRemovedIds(array $persistedOptions, array $value): array
    {
        $existingIds = [];
        foreach ($persistedOptions as $persistedOption) {
            $existingIds[] = $persistedOption->getId();
        }

        $passedIds = array_filter(array_column($value, 'id'), static function ($value) {
            return null !== $value && '' !== $value;
        });

        return array_diff($existingIds, $passedIds);
    }

    private function getProductVariantsByOptions(string $field, array $removedIds): array
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $this->registry->getRepository(Product::class);

        return $productRepository->findParentSkusByAttributeOptions(
            Product::TYPE_SIMPLE,
            $field,
            $removedIds
        );
    }
}
