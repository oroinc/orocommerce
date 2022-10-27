<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that the currency is allowed for the current price list.
 */
class ProductPriceCurrencyValidator extends ConstraintValidator
{
    /**
     * @param BaseProductPrice|object $value
     * @param ProductPriceCurrency $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof BaseProductPrice) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value must be instance of "%s", "%s" given',
                    BaseProductPrice::class,
                    is_object($value) ? ClassUtils::getClass($value) : gettype($value)
                )
            );
        }

        $price = $value->getPrice();
        if (!$price) {
            return;
        }
        $currency = $price->getCurrency();
        if (!$currency) {
            return;
        }
        if ($value->getPriceList() === null) {
            return;
        }

        $availableCurrencies = $value->getPriceList()->getCurrencies();
        if (!in_array($currency, $availableCurrencies, true)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('price.currency')
                ->setParameters(['%invalidCurrency%' => $currency])
                ->addViolation();
        }
    }
}
