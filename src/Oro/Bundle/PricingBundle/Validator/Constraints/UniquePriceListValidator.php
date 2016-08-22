<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

use Oro\Bundle\PricingBundle\Entity\PriceListAwareInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;

class UniquePriceListValidator extends ConstraintValidator
{
    const PRICE_LIST_KEY = 'priceList';

    /**
     * Checks if the passed value is valid.
     *
     * @param PriceListAwareInterface[] $value The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var ExecutionContext $context */
        $context = $this->context;

        $ids = [];
        foreach ($value as $index => $item) {
            if (null === $id = $this->getPriceListId($item)) {
                continue;
            }
            if (in_array($id, $ids, true)) {
                $path = $this->getViolationPath($item, $index);
                $context->buildViolation($constraint->message, [])
                    ->atPath($path)
                    ->addViolation();
            }
            $ids[] = $id;
        }
    }

    /**
     * @param PriceListAwareInterface|array $item
     * @return int
     */
    protected function getPriceListId($item)
    {
        if ($item instanceof PriceListAwareInterface && $item->getPriceList()) {
            return $item->getPriceList()->getId();
        } elseif (is_array($item) &&
            array_key_exists(self::PRICE_LIST_KEY, $item) &&
            $item[self::PRICE_LIST_KEY] instanceof PriceList
        ) {
            /** @var PriceList $priceList */
            $priceList = $item[self::PRICE_LIST_KEY];
            return $priceList->getId();
        }

        return null;
    }

    /**
     * @param mixed $item
     * @param integer $index
     * @return string
     */
    protected function getViolationPath($item, $index)
    {
        if ($item instanceof PriceListAwareInterface) {
            return "[$index].".self::PRICE_LIST_KEY;
        } elseif (is_array($item) && array_key_exists(self::PRICE_LIST_KEY, $item)) {
            return "[$index][".self::PRICE_LIST_KEY.']';
        }

        return '';
    }
}
