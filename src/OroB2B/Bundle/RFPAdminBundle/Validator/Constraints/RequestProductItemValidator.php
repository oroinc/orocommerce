<?php

namespace OroB2B\Bundle\RFPAdminBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem as RequestProductItemEntity;

class RequestProductItemValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param RequestProductItemEntity $requestProductItem
     * @param Constraint $constraint
     */
    public function validate($requestProductItem, Constraint $constraint)
    {
        if (!$requestProductItem instanceof RequestProductItemEntity) {
            throw new UnexpectedTypeException(
                $requestProductItem,
                'OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem'
            );
        }

        if (null === ($requestProduct = $requestProductItem->getRequestProduct())) {
            return $this->addViolation($constraint);
        }

        if (null === ($product = $requestProduct->getProduct())) {
            return $this->addViolation($constraint);
        }

        if (null === ($allowedUnits = $product->getAvailableUnitCodes())) {
            return $this->addViolation($constraint);
        }

        if (null === ($productUnit = $requestProductItem->getProductUnit())) {
            return $this->addViolation($constraint);
        }

        if (!in_array($productUnit->getCode(), $allowedUnits, true)) {
            return $this->addViolation($constraint);
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
