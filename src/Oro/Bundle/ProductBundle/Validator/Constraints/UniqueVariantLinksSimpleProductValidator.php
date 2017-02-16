<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\ProductBundle\Entity\Product;

class UniqueVariantLinksSimpleProductValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_unique_variant_links_simple_product';

    /** @var ValidatorInterface */
    private $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
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
        foreach ($value->getParentVariantLinks() as $parentVariantLink) {
            $parentProduct = $parentVariantLink->getParentProduct();
            $violationsList = $this->validator->validate($parentProduct, new UniqueProductVariantLinks());

            if ($violationsList->count() > 0) {
                $productsSku[] = $parentProduct->getSku();
            }
        }

        if ($productsSku) {
            $this->context->addViolation($constraint->message, ['%products%' => implode(', ', $productsSku)]);
        }
    }
}
