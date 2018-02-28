<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\PricingBundle\Entity\BaseProductPrice;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

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
        if ($value->getPriceList() === null) {
            return;
        }

        $availableCurrencies = $value->getPriceList()->getCurrencies();
        $currency = $price->getCurrency();
        if (!in_array($currency, $availableCurrencies, true)) {
            $this->context->buildViolation($constraint->message)
                ->atPath('price.currency')
                ->setParameters(['%invalidCurrency%' => $currency])
                ->addViolation();
        }
    }
}
