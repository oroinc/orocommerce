<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates product variant field configurations.
 *
 * This validator checks that variant fields selected for configurable products are valid custom fields
 * that exist in the product's attribute family and can be used for product variation.
 */
class ProductVariantFieldValidator extends ConstraintValidator
{
    public const ALIAS = 'oro_product_variant_field';

    /** @var CustomFieldProvider */
    protected $customFieldProvider;

    public function __construct(CustomFieldProvider $customFieldProvider)
    {
        $this->customFieldProvider = $customFieldProvider;
    }

    /**
     * @param Product $value
     * @param ProductVariantField|Constraint $constraint
     */
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        $productClass = ClassUtils::getClass($value);
        $customProductFields = $this->customFieldProvider->getEntityCustomFields($productClass);

        foreach ($value->getVariantFields() as $field) {
            if (!array_key_exists($field, $customProductFields)) {
                $this->context->addViolation($constraint->message, ['{{ field }}' => $field]);
            }
        }
    }
}
