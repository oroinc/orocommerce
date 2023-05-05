<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate configurable attribute combinations is unique.
 */
class UniqueProductVariantLinksValidator extends ConstraintValidator
{
    use ConfigurableProductAccessorTrait;

    const ALIAS = 'oro_product_unique_variant_links';

    /**
     * @var PropertyAccessor
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
     * @param UniqueProductVariantLinks|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $product = $this->getConfigurableProduct($value, $constraint);
        if ($product === null) {
            return;
        }
        if (count($product->getVariantFields()) === 0) {
            return null;
        }

        $this->validateUniqueVariantLinks($product, $constraint);
    }

    private function validateUniqueVariantLinks(Product $value, UniqueProductVariantLinks $constraint)
    {
        $variantHashes = [];
        $variantFields = $value->getVariantFields();
        $simpleProducts = $this->getSimpleProducts($value);

        foreach ($simpleProducts as $product) {
            if ($product) {
                $variantHashes[] = $this->getVariantFieldsHash($variantFields, $product);
            }
        }

        if (count($variantHashes) !== count(array_unique($variantHashes))) {
            $this->context->addViolation($constraint->uniqueRequiredMessage);
        }
    }

    /**
     * @param array $variantFields
     * @param Product $product
     * @return string
     */
    private function getVariantFieldsHash(array $variantFields, Product $product)
    {
        $fields = [];
        foreach ($variantFields as $fieldName) {
            if ($this->propertyAccessor->isReadable($product, $fieldName)) {
                $fieldValue = $this->propertyAccessor->getValue($product, $fieldName);
                $fields[$fieldName] = $fieldValue;

                if (is_object($fieldValue) && method_exists($fieldValue, '__toString')) {
                    $fields[$fieldName] = (string)$fieldValue;
                }
            }
        }

        return md5(json_encode($fields));
    }

    /**
     * @param Product $value
     * @return array|Product[]
     */
    private function getSimpleProducts(Product $value)
    {
        $variantLinks = $value->getVariantLinks();
        if ($value->getId()
            && $variantLinks instanceof AbstractLazyCollection
            && !$variantLinks->isInitialized()
        ) {
            $repo = $this
                ->registry
                ->getManagerForClass(ProductVariantLink::class)
                ->getRepository(ProductVariantLink::class);

            // variantLinksInDb
            $persistedProductVariantLinks = $repo->findBy([
                'parentProduct' => $value
            ]);

            $variantLinkCollection = [];
            // Merge items from DB with newly added or changes items
            if ($variantLinks instanceof PersistentCollection) {
                $variantLinkCollection = $variantLinks->unwrap()->toArray();
            }

            return array_map(function (ProductVariantLink $productVariantLink) {
                return $productVariantLink->getProduct();
            }, array_merge($variantLinkCollection, $persistedProductVariantLinks));
        }

        $simpleProducts = [];
        foreach ($variantLinks as $variantLink) {
            if ($variantLink->getProduct()) {
                $simpleProducts[] = $variantLink->getProduct();
            }
        }

        return $simpleProducts;
    }
}
