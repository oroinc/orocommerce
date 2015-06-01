<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;

class UniqueProductPricesValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_array($value) && !($value instanceof \Traversable && $value instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($value, 'array or Traversable and ArrayAccess');
        }

        $productPricesByHash = [];

        foreach ($value as $productPrice) {
            if (!$productPrice instanceof ProductPrice) {
                throw new UnexpectedTypeException($value, 'OroB2B\Bundle\PricingBundle\Entity\ProductPrice');
            }

            $hash = $this->getHash($productPrice);

            if (array_key_exists($hash, $productPricesByHash)) {
                /** @var UniqueProductPrices $constraint */
                $this->context->addViolation($constraint->message);
                break;
            } else {
                $productPricesByHash[$hash] = $productPrice;
            }
        }
    }

    /**
     * @param ProductPrice $productPrice
     * @return string
     */
    protected function getHash(ProductPrice $productPrice)
    {
        return md5(
            sprintf(
                '%s_%s_%s_%s_%s',
                $productPrice->getProduct()->getId(),
                $productPrice->getPriceList()->getId(),
                $productPrice->getQuantity(),
                $productPrice->getUnit()->getCode(),
                $productPrice->getPrice()->getCurrency()
            )
        );
    }
}
