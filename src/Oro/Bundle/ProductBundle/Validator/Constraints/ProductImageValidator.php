<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\ProductBundle\Entity\ProductImage as ProductImageEntity;

class ProductImageValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_image_validator';

    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @param ValidatorInterface $validator
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param ProductImageEntity $value
     * @param Constraint $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value->getImage() || (!$value->getImage()->getFilename() && null === $value->getImage()->getFile())) {
            $this->context
                ->buildViolation($constraint->message)
                ->addViolation();
        }

        if (!$value->getProduct() || ($productImages = $value->getProduct()->getImages())->contains($value)) {
            return;
        }

        // add new product image to existing collection and validate
        $productImages->add($value);
        $violations = $this->validator->validate($productImages, new ProductImageCollection());

        if ($violations->count()) {
            foreach ($violations as $violation) {
                /** @var ConstraintViolation $violation */
                $this->context->addViolation(
                    $violation->getMessage(),
                    $violation->getParameters()
                );
            }
        }
    }
}
