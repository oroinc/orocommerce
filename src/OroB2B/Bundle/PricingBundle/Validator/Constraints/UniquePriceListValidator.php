<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use OroB2B\Bundle\PricingBundle\SystemConfig\PriceListConfigBag;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

class UniquePriceListValidator extends ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param PriceListConfigBag $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var ExecutionContext $context */
        $context = $this->context;

        $allIds = [];
        $duplicatePriceLists = [];

        foreach ($value->getConfigs() as $priceList) {
            $id = $priceList->getPriceList()->getId();
            if (in_array($id, $allIds, true)) {
                $duplicatePriceLists[] = $priceList->getPriceList()->getName();
            }
            $allIds[] = $id;
        }

        if (0 !== count($duplicatePriceLists)) {
            $duplicatePriceLists = array_unique($duplicatePriceLists);
            $context->buildViolation($constraint->message, [
                'priceLists' => implode(', ', $duplicatePriceLists)
            ])
                ->atPath('value')
                ->addViolation();
        }
    }
}
