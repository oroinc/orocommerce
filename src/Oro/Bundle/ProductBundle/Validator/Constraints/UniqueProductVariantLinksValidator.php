<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validate configurable attribute combinations is unique.
 */
class UniqueProductVariantLinksValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_unique_variant_links';

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @var ManagerRegistry
     */
    private $registry;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param ManagerRegistry $registry
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Product $value
     * @param UniqueProductVariantLinks|Constraint $constraint
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

        if (!$value->isConfigurable()) {
            return;
        }

        if (count($value->getVariantFields()) === 0) {
            return;
        }

        $this->validateUniqueVariantLinks($value, $constraint);
    }

    /**
     * @param Product $value
     * @param UniqueProductVariantLinks $constraint
     */
    private function validateUniqueVariantLinks(Product $value, UniqueProductVariantLinks $constraint)
    {
        $variantHashes = [];
        $variantFields = $value->getVariantFields();
        $simpleProducts = $this->getSimpleProducts($value);

        foreach ($simpleProducts as $product) {
            $variantHashes[] = $this->getVariantFieldsHash($variantFields, $product);
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
            /** @var ProductRepository $repo */
            $repo = $this->registry->getManagerForClass(Product::class)->getRepository(Product::class);

            return $repo->getSimpleProductsForConfigurableProduct($value);
        }

        $simpleProducts = [];
        foreach ($value->getVariantLinks() as $variantLink) {
            if ($variantLink->getProduct()) {
                $simpleProducts[] = $variantLink->getProduct();
            }
        }

        return $simpleProducts;
    }
}
