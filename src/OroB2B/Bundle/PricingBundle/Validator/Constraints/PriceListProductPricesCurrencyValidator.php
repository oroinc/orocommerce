<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;

class PriceListProductPricesCurrencyValidator extends ConstraintValidator
{
    /**
     * @param ProductPrice|object $value
     * @param ProductPriceCurrency $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof BasePriceList) {
            throw new UnexpectedTypeException($value, 'OroB2B\Bundle\PricingBundle\Entity\PriceList');
        }

        $availableCurrencies = $value->getCurrencies();

        $invalidCurrencies = [];

        /** @var ProductPrice $productPrice */
        foreach ($value->getPrices() as $productPrice) {
            $price = $productPrice->getPrice();
            if ($price && !in_array($price->getCurrency(), $availableCurrencies, true)) {
                $invalidCurrencies[$price->getCurrency()] = true;
            }
        }

        foreach (array_keys($invalidCurrencies) as $currency) {
            $this->context->addViolationAt('currencies', $constraint->message, ['%invalidCurrency%' => $currency]);
        }
    }
}
