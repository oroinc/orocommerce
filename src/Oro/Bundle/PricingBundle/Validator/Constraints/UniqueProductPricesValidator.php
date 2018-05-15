<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validate all product prices to be unique within given collection of values.
 */
class UniqueProductPricesValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!\is_array($value) && !($value instanceof \Traversable && $value instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($value, 'array or Traversable and ArrayAccess');
        }

        $productPricesByHash = [];

        foreach ($value as $productPrice) {
            if (!$productPrice instanceof ProductPrice) {
                throw new UnexpectedTypeException($productPrice, ProductPrice::class);
            }

            $hash = $this->getHash($productPrice);

            if (array_key_exists($hash, $productPricesByHash)) {
                /** @var UniqueProductPrices $constraint */
                $this->context->addViolation($constraint->message);
                break;
            } else {
                $productPricesByHash[$hash] = true;
            }
        }
    }

    /**
     * @param ProductPrice $productPrice
     * @return string
     */
    protected function getHash(ProductPrice $productPrice)
    {
        $key = sprintf(
            '%s_%s_%F_%s',
            // SKU is unique, id can not be used as validator is also called for new products
            $productPrice->getProduct()->getSku(),
            // Price list creation is separated from prices, only id is unique
            $productPrice->getPriceList()->getId(),
            $productPrice->getQuantity(),
            // Unit code is unique
            $productPrice->getUnit()->getCode()
        );

        if ($productPrice->getPrice()) {
            $key .= '_' . $productPrice->getPrice()->getCurrency();
        }

        return md5($key);
    }
}
