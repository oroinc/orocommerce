<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate that all required configurable fields are not empty.
 */
class EmptyVariantFieldInSimpleProductForVariantLinksValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_empty_variant_field_in_simple_product_for_variant_links';

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(PropertyAccessorInterface $propertyAccessor, ManagerRegistry $registry)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->registry = $registry;
    }

    /**
     * @param Product $value
     * @param UniqueVariantLinksSimpleProduct|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_a($value, Product::class)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entity must be instance of "%s", "%s" given',
                    Product::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if ($value->isConfigurable()) {
            return;
        }

        $this->validateEmptyVariantField($value, $constraint);
    }

    /**
     * @param Product $product
     * @param UniqueVariantLinksSimpleProduct|Constraint $constraint
     */
    private function validateEmptyVariantField(Product $product, Constraint $constraint)
    {
        $errorsData = [];
        [$variantFields, $attributeToSkus] = $this->getPreparedVariantFields($product);
        if (!$variantFields) {
            return;
        }

        foreach ($variantFields as $variantField) {
            if ($this->propertyAccessor->isReadable($product, $variantField)) {
                $productValue = $this->propertyAccessor->getValue($product, $variantField);

                if ($productValue === null) {
                    $errorsData[$variantField] = $attributeToSkus[$variantField];
                }
            }
        }

        if ($errorsData) {
            foreach ($errorsData as $variantField => $parentProductsSku) {
                $this->context->addViolation(
                    $constraint->message,
                    [
                        '%variantField%' => $variantField,
                        '%products%' => implode(', ', $parentProductsSku)
                    ]
                );
            }
        }
    }

    private function getRequiredAttributesInfo(Product $product): array
    {
        $parentVariantLinks = $product->getParentVariantLinks();
        if ($product->getId()
            && $parentVariantLinks instanceof AbstractLazyCollection
            && !$parentVariantLinks->isInitialized()
        ) {
            /** @var ProductRepository $repo */
            $repo = $this->registry->getManagerForClass(Product::class)->getRepository(Product::class);

            return $repo->getRequiredAttributesForSimpleProduct($product);
        }

        $attributesInfo = [];
        foreach ($parentVariantLinks as $parentVariantLink) {
            $parentProduct = $parentVariantLink->getParentProduct();

            $attributesInfo[] = [
                'id' => $parentProduct->getId(),
                'sku' => $parentProduct->getSku(),
                'variantFields' => $parentProduct->getVariantFields()
            ];
        }

        return $attributesInfo;
    }

    private function getPreparedVariantFields(Product $product): array
    {
        $requiredAttributesInfo = $this->getRequiredAttributesInfo($product);
        $attributeToSkus = [];
        $variantFields = [];
        if (!$requiredAttributesInfo) {
            return [$variantFields, $attributeToSkus];
        }

        foreach ($requiredAttributesInfo as $item) {
            foreach ($item['variantFields'] as $variantField) {
                $attributeToSkus[$variantField][] = $item['sku'];
            }
            $variantFields[] = $item['variantFields'];
        }
        if (count($variantFields)) {
            $variantFields = array_unique(array_merge(...$variantFields));
        }

        return [$variantFields, $attributeToSkus];
    }
}
