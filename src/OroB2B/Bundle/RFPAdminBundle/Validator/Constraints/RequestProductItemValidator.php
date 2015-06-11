<?php

namespace OroB2B\Bundle\RFPAdminBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem as RequestProductItemEntity;

class RequestProductItemValidator extends ConstraintValidator
{
    /**
     * @param RequestProductItemEntity $requestProductItem
     * @param RequestProductItem $constraint
     * {@inheritdoc}
     */
    public function validate($requestProductItem, Constraint $constraint)
    {
        if (!$requestProductItem instanceof RequestProductItemEntity) {
            throw new UnexpectedTypeException(
                $requestProductItem,
                'OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem'
            );
        }
        /** @var $product Product */
        $product = $requestProductItem->getRequestProduct()->getProduct();
        $allowedUnits = $product ? $product->getAvailableUnitCodes() : [];
        if (!in_array($requestProductItem->getProductUnit()->getCode(), $allowedUnits)) {
            $this->context->addViolationAt('productUnit', $constraint->message);
        }
    }
}
