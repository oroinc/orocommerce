<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use OroB2B\Bundle\ProductBundle\Model\ProductDataConverter;

class ProductBySkuValidator extends ConstraintValidator
{
    /** @var $converter */
    protected $converter;

    /**
     * @param ProductDataConverter $converter
     */
    public function __construct(ProductDataConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @param string $value
     * @param Constraint|ProductBySku $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value) {
            $product = $this->converter->convertSkuToProduct($value);
            if (!$product) {
                $this->context->addViolation($constraint->message);
            }
        }
    }
}
