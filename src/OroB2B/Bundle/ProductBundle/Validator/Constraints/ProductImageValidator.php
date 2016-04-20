<?php

namespace OroB2B\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\ProductBundle\Entity\ProductImage as ProductImageEntity;

class ProductImageValidator extends ConstraintValidator
{
    const ALIAS = 'orob2b_product_image_validator';

    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @param ProductImageEntity $value
     * @param Constraint|ProductImageCollection $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value->getImage() || (!$value->getImage()->getFilename() && !$value->getImage()->getFile())) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
