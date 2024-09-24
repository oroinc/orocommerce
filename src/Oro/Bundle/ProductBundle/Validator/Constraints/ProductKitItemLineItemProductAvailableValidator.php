<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator checking if the product specified in a kit item line item is allowed for
 *  the related {@see ProductKitItem}.
 */
class ProductKitItemLineItemProductAvailableValidator extends ConstraintValidator
{
    private EntityStateChecker $entityStateChecker;

    public function __construct(EntityStateChecker $entityStateChecker)
    {
        $this->entityStateChecker = $entityStateChecker;
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemLineItemProductAvailable) {
            throw new UnexpectedTypeException($constraint, ProductKitItemLineItemProductAvailable::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof Product) {
            throw new UnexpectedValueException($value, Product::class);
        }

        $kitItemLineItem = $this->context->getObject();
        if (!$kitItemLineItem instanceof ProductKitItemLineItemInterface) {
            throw new UnexpectedValueException($kitItemLineItem, ProductKitItemLineItemInterface::class);
        }

        if ($kitItemLineItem->getKitItem() === null) {
            return;
        }

        if ($this->skipValidation($kitItemLineItem, $constraint)) {
            return;
        }

        $kitItemProduct = $kitItemLineItem->getKitItem()->getKitItemProduct($value);
        if ($kitItemProduct === null) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ product_sku }}', $this->formatValue($value->getSku()))
                ->setCode($constraint::PRODUCT_NOT_ALLOWED)
                ->setCause($value)
                ->addViolation();
        } elseif ($constraint->availabilityValidationGroups) {
            $this->context
                ->getValidator()
                ->inContext($this->context)
                ->validate(
                    $kitItemProduct,
                    null,
                    ValidationGroupUtils::resolveValidationGroups($constraint->availabilityValidationGroups)
                );
        }
    }

    private function skipValidation(
        ProductKitItemLineItemInterface $kitItemLineItem,
        ProductKitItemLineItemProductAvailable $constraint
    ): bool {
        return $constraint->ifChanged
            && !$this->entityStateChecker->isNewEntity($kitItemLineItem)
            && !$this->entityStateChecker->isChangedEntity($kitItemLineItem, $constraint->ifChanged);
    }
}
