<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate that configurable product has selected configurable attributes and these attributes are filled in variants.
 */
class ProductVariantLinksValidator extends ConstraintValidator
{
    use ConfigurableProductAccessorTrait;

    const ALIAS = 'oro_product_variant_links';

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object $value
     * @param ProductVariantLinks|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $product = $this->getConfigurableProduct($value, $constraint);
        if ($product === null) {
            return;
        }

        $this->validateLinksWithoutFields($product, $constraint);
        if ($value instanceof ProductVariantLink) {
            $this->validateVariantLinksCollectionHaveFilledFIelds([$value], $product->getVariantFields(), $constraint);
        } else {
            $this->validateLinksHaveFilledFields($product, $constraint);
        }
    }

    /**
     * Add violation if variant fields are empty but variant links are present
     *
     * @param Product $value
     * @param ProductVariantLinks $constraint
     */
    private function validateLinksWithoutFields(Product $value, ProductVariantLinks $constraint)
    {
        if (count($value->getVariantFields()) === 0 && $value->getVariantLinks()->count() !== 0) {
            $this->context->buildViolation($constraint->variantFieldRequiredMessage)
                ->atPath('variantFields')
                ->addViolation();
        }
    }

    /**
     * @param Product $value
     * @param ProductVariantLinks $constraint
     */
    private function validateLinksHaveFilledFields(Product $value, ProductVariantLinks $constraint)
    {
        $variantFields = $value->getVariantFields();

        // Validate only loaded collection items
        $variantLinks = $value->getVariantLinks();
        if ($variantLinks instanceof PersistentCollection) {
            $variantLinks = $variantLinks->unwrap();
        }

        $this->validateVariantLinksCollectionHaveFilledFIelds($variantLinks, $variantFields, $constraint);
    }

    /**
     * @param iterable|ProductVariantLink[] $variantLinks
     * @param array $variantFields
     * @param ProductVariantLinks $constraint
     */
    private function validateVariantLinksCollectionHaveFilledFIelds(
        $variantLinks,
        array $variantFields,
        ProductVariantLinks $constraint
    ): void {
        $errorsData = [];
        foreach ($variantLinks as $variantLink) {
            $product = $variantLink->getProduct();
            if (!$product) {
                continue;
            }

            foreach ($variantFields as $variantField) {
                if ($this->propertyAccessor->isReadable($product, $variantField)) {
                    $variantFieldValue = $this->propertyAccessor->getValue($product, $variantField);

                    if ($variantFieldValue === null) {
                        $errorsData[$product->getSku()][] = $variantField;
                    }
                }
            }
        }

        foreach ($errorsData as $productSku => $fields) {
            $this->context->addViolation(
                $constraint->variantLinkHasNoFilledFieldMessage,
                [
                    '%product_sku%' => $productSku,
                    '%fields%' => implode(', ', $fields)
                ]
            );
        }
    }
}
