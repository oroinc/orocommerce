<?php

namespace OroB2B\Bundle\RFPBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\RFPBundle\Entity\RequestProductItem as RequestProductItemEntity;

class RequestProductItemValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param RequestProductItemEntity $requestProductItem
     * @param Constraint|RequestProductItem $constraint
     */
    public function validate($requestProductItem, Constraint $constraint)
    {
        if (!$requestProductItem instanceof RequestProductItemEntity) {
            throw new UnexpectedTypeException(
                $requestProductItem,
                'OroB2B\Bundle\RFPBundle\Entity\RequestProductItem'
            );
        }

        if (null === ($requestProduct = $requestProductItem->getRequestProduct())) {
            $this->addViolation($constraint);
            return;
        }

        if (null === ($product = $requestProduct->getProduct())) {
            $this->addViolation($constraint);
            return;
        }

        if ([] === ($allowedUnits = $product->getAvailableUnitCodes())) {
            $this->addViolation($constraint);
            return;
        }

        if (null === ($productUnit = $requestProductItem->getProductUnit())) {
            $this->addViolation($constraint);
            return;
        }

        if (!in_array($productUnit->getCode(), $allowedUnits, true)) {
            $this->addViolation($constraint);
            return;
        }
    }

    /**
     * @param RequestProductItem $constraint
     */
    protected function addViolation(RequestProductItem $constraint)
    {
        $this->context->addViolationAt('productUnit', $constraint->message);
    }
}
