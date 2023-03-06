<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
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
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor)
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

        $variantLinks = $value instanceof ProductVariantLink
            ? new ArrayCollection([$value])
            : $this->getLoadedVariantLinks($product);

        $this->validateVariantLinksFamily($variantLinks, $constraint);
        $this->validateVariantLinksCollectionHaveFilledFields($variantLinks, $product->getVariantFields(), $constraint);
    }

    /**
     * Add violation if variant fields are empty but variant links are present
     */
    private function validateLinksWithoutFields(Product $value, ProductVariantLinks $constraint): void
    {
        if (count($value->getVariantFields()) === 0 && $value->getVariantLinks()->count() !== 0) {
            $this->context->buildViolation($constraint->variantFieldRequiredMessage)
                ->atPath('variantFields')
                ->addViolation();
        }
    }

    /**
     * @param Product $product
     * @return Collection|ProductVariantLink[]
     */
    private function getLoadedVariantLinks(Product $product)
    {
        // Validate only loaded collection items
        $variantLinks = $product->getVariantLinks();

        return $variantLinks instanceof PersistentCollection ? $variantLinks->unwrap() : $variantLinks;
    }

    /**
     * @param iterable|ProductVariantLink[] $variantLinks
     * @param array $variantFields
     * @param ProductVariantLinks $constraint
     */
    private function validateVariantLinksCollectionHaveFilledFields(
        iterable $variantLinks,
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

    /**
     * Configurable product and product variant(s) should belongs to the same product family
     *
     * @param iterable|ProductVariantLink[] $variantLinks
     * @param ProductVariantLinks $constraint
     */
    private function validateVariantLinksFamily(iterable $variantLinks, ProductVariantLinks $constraint): void
    {
        $variantLinksLabels = [];
        $variantLinksFromAnotherFamily = $this->getVariantLinksFromAnotherFamily($variantLinks);
        foreach ($variantLinksFromAnotherFamily as $variantLink) {
            $variantLinks->removeElement($variantLink);
            $variantLinksLabels[] = $variantLink->getProduct()->getSku();
        }

        if (!empty($variantLinksLabels)) {
            $this->context->addViolation(
                $constraint->variantLinkBelongsAnotherFamilyMessage,
                ['%products_sku%' => implode(', ', $variantLinksLabels)]
            );
        }
    }

    /**
     * @param iterable|ProductVariantLink[] $variantLinks
     * @return array
     */
    private function getVariantLinksFromAnotherFamily(iterable $variantLinks): array
    {
        $variantLinksFromAnotherFamily = [];
        foreach ($variantLinks as $variantLink) {
            $product = $variantLink->getProduct();
            $parentProduct = $variantLink->getParentProduct();
            if (!$product || !$parentProduct) {
                continue;
            }

            $productAttributeFamily = $product->getAttributeFamily();
            $parentProductAttributeFamily = $parentProduct->getAttributeFamily();
            if (!$productAttributeFamily ||
                !$parentProductAttributeFamily ||
                $productAttributeFamily->getCode() !== $parentProductAttributeFamily->getCode()
            ) {
                $variantLinksFromAnotherFamily[] = $variantLink;
            }
        }

        return $variantLinksFromAnotherFamily;
    }
}
