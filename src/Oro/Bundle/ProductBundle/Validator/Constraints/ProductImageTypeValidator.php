<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImageType as ProductImageTypeEntity;

class ProductImageTypeValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_image_type_validator';

    /**
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @param ImageTypeProvider $imageTypeProvider
     */
    public function __construct(ImageTypeProvider $imageTypeProvider)
    {
        $this->imageTypeProvider = $imageTypeProvider;
    }

    /**
     * @param ProductImageTypeEntity $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value->getType()) {
            return;
        }

        $validTypes = array_keys($this->imageTypeProvider->getImageTypes());
        if (!in_array($value->getType(), $validTypes)) {
            $this->context
                ->buildViolation($constraint->message, ['%type%' => $value->getType()])
                ->addViolation();
        }
    }
}
