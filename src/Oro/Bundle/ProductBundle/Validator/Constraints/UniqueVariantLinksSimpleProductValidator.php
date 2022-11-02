<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validate that combination of additional fields is unique for each configurable product where the current one is used.
 */
class UniqueVariantLinksSimpleProductValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_unique_variant_links_simple_product';

    /** @var ValidatorInterface */
    private $validator;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    public function __construct(ValidatorInterface $validator, ManagerRegistry $registry)
    {
        $this->validator = $validator;
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

        if ($value->isConfigurable() || $value->getParentVariantLinks()->count() === 0) {
            return;
        }

        $this->validateUniqueVariantLinks($value, $constraint);
    }

    /**
     * @param Product $value
     * @param UniqueVariantLinksSimpleProduct|Constraint $constraint
     */
    private function validateUniqueVariantLinks(Product $value, Constraint $constraint)
    {
        $productsSku = [];
        $parentProducts = $this->getParentProducts($value);
        foreach ($parentProducts as $parentProduct) {
            $violationsList = $this->validator->validate($parentProduct, new UniqueProductVariantLinks());

            if ($violationsList->count() > 0) {
                $productsSku[] = $parentProduct->getSku();
            }
        }

        if ($productsSku) {
            $this->context->addViolation($constraint->message, ['%products%' => implode(', ', $productsSku)]);
        }
    }

    /**
     * @param Product $value
     * @return array|Product[]
     */
    private function getParentProducts(Product $value)
    {
        $parentVariantLinks = $value->getParentVariantLinks();
        if ($value->getId()
            && $parentVariantLinks instanceof AbstractLazyCollection
            && !$parentVariantLinks->isInitialized()
        ) {
            $repo = $this
                ->registry
                ->getManagerForClass(ProductVariantLink::class)
                ->getRepository(ProductVariantLink::class);

            // variantLinksInDb
            $persistedParentProductVariantLinks = $repo->findBy([
                'product' => $value
            ]);

            $parentVariantLinkCollection = [];
            // Merge items from DB with newly added or changes items
            if ($parentVariantLinks instanceof PersistentCollection) {
                $parentVariantLinkCollection = $parentVariantLinks->unwrap()->toArray();
            }

            return array_map(function (ProductVariantLink $productVariantLink) {
                return $productVariantLink->getParentProduct();
            }, array_merge($parentVariantLinkCollection, $persistedParentProductVariantLinks));
        }

        $parentProducts = [];
        foreach ($parentVariantLinks as $parentVariantLink) {
            $parentProducts[] = $parentVariantLink->getParentProduct();
        }

        return $parentProducts;
    }
}
