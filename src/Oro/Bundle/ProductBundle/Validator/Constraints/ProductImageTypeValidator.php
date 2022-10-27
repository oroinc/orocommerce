<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImageType as EntityProductImageType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates ProductImageType.
 */
class ProductImageTypeValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_image_type_validator';

    /**
     * @var ImageTypeProvider $imageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var TranslatorInterface $translator
     */
    protected $translator;

    /**
     * @var ExecutionContextInterface $context
     */
    protected $context;

    public function __construct(
        ImageTypeProvider $imageTypeProvider,
        TranslatorInterface $translator
    ) {
        $this->imageTypeProvider = $imageTypeProvider;
        $this->translator = $translator;
    }

    /**
     * @param EntityProductImageType $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value->getType()) {
            return;
        }

        /** @var ThemeImageType[] $availableTypes */
        $validTypes = $this->imageTypeProvider->getImageTypes();
        $this->validateType($value, $constraint, $validTypes);
        $this->validateDuplicateType($value, $constraint, $validTypes);
    }

    private function validateType(EntityProductImageType $value, Constraint $constraint, $validTypes)
    {
        $validTypeNames = array_keys($validTypes);

        if (!in_array($value->getType(), $validTypeNames)) {
            $this->context
                ->buildViolation(
                    $constraint->invalid_type_message,
                    [
                        '%type%' => $value->getType()
                    ]
                )
                ->addViolation();
        }
    }

    private function validateDuplicateType(EntityProductImageType $value, $constraint, $validTypes)
    {
        if (null === $value->getProductImage() ||
            ($existingProductImageTypes = $value->getProductImage()->getTypes())->contains($value)
        ) {
            return;
        }

        if ($existingProductImageTypes->containsKey($value->getType())) {
            $this->context
                ->buildViolation(
                    $constraint->already_exists_message,
                    [
                        '%type%' => $this->translator->trans((string) $validTypes[$value->getType()]->getLabel())
                    ]
                )
                ->addViolation();
        }
    }
}
