<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;

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
                throw new UnexpectedTypeException($productPrice, 'Oro\Bundle\PricingBundle\Entity\ProductPrice');
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
        $key = sprintf(
            '%s_%s_%F_%s',
            $productPrice->getProduct(),
            $productPrice->getPriceList(),
            $productPrice->getQuantity(),
            $productPrice->getUnit()
        );

        if ($productPrice->getPrice()) {
            $key .= '_' . $productPrice->getPrice()->getCurrency();
        }

        return md5($key);
    }
}
