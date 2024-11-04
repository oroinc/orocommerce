<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Oro\Bundle\EntityBundle\Tools\EntityStateChecker;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator checking if the product unit specified in a {@see OrderProductKitItemLineItem} is allowed for
 * the related {@see ProductKitItem}.
 */
class ProductKitItemLineItemProductUnitAvailableValidator extends ConstraintValidator
{
    private EntityStateChecker $entityStateChecker;

    public function __construct(EntityStateChecker $entityStateChecker)
    {
        $this->entityStateChecker = $entityStateChecker;
    }

    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ProductKitItemLineItemProductUnitAvailable) {
            throw new UnexpectedTypeException($constraint, ProductKitItemLineItemProductUnitAvailable::class);
        }

        if ($value === null) {
            return;
        }

        if (!$value instanceof ProductUnit) {
            throw new UnexpectedValueException($value, ProductUnit::class);
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

        if ($kitItemLineItem->getKitItem()->getProductUnit() !== $value) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ product_unit }}', $this->formatValue($value->getCode()))
                ->setCode($constraint::UNIT_NOT_ALLOWED)
                ->setCause($value)
                ->addViolation();
        }
    }

    private function skipValidation(
        ProductKitItemLineItemInterface $kitItemLineItem,
        ProductKitItemLineItemProductUnitAvailable $constraint
    ): bool {
        return $constraint->ifChanged
            && !$this->entityStateChecker->isNewEntity($kitItemLineItem)
            && !$this->entityStateChecker->isChangedEntity($kitItemLineItem, $constraint->ifChanged);
    }
}
