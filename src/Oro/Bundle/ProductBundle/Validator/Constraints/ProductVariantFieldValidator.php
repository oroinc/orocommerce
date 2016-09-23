<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;

class ProductVariantFieldValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_variant_field';

    /** @var CustomFieldProvider */
    protected $customFieldProvider;

    /**
     * @param CustomFieldProvider $customFieldProvider
     */
    public function __construct(CustomFieldProvider $customFieldProvider)
    {
        $this->customFieldProvider = $customFieldProvider;
    }

    /**
     * @param Product $value
     * @param ProductVariantField|Constraint $constraint
     */
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
