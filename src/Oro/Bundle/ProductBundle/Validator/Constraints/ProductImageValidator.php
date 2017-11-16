<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\ProductBundle\Entity\ProductImage as EntityProductImage;

class ProductImageValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_image_validator';

    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @param EntityProductImage $value
     * @param Constraint $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value->getImage() ||
            (!$value->getImage()->getFilename() && null === $value->getImage()->getFile())
        ) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
