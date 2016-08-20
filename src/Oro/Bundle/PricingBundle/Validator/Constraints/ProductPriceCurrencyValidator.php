<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;

class ProductPriceCurrencyValidator extends ConstraintValidator
{
    /**
     * @param ProductPrice|object $value
     * @param ProductPriceCurrency $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof ProductPrice) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    'Oro\Bundle\PricingBundle\Entity\ProductPrice',
                    is_object($value) ? ClassUtils::getClass($value) : gettype($value)
                )
            );
        }

        $price = $value->getPrice();
        if (!$price) {
            return;
        }

        $availableCurrencies = $value->getPriceList()->getCurrencies();
        $currency = $price->getCurrency();
        if (!in_array($currency, $availableCurrencies, true)) {
            $this->context->addViolationAt('price.currency', $constraint->message, ['%invalidCurrency%' => $currency]);
        }
    }
}
