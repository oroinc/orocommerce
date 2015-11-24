<?php

namespace OroB2B\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

use OroB2B\Bundle\PricingBundle\Entity\PriceListAwareInterface;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;

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
        $i = 0;
        foreach ($value as $index => $item) {
            if (null === $id = $this->getPriceListId($item)) {
                continue;
            }
            if (in_array($id, $ids, true)) {
                $path = $context->getPropertyPath();
                $context->buildViolation($constraint->message, [])
                    ->atPath("[$i].priceList")
                    ->addViolation();
            }
            $ids[] = $id;
            //children[oro_b2b_pricing___default_price_lists].children[value].children[configs].data[1].priceList
            //children[oro_b2b_pricing___default_price_lists].children[value].children[configs].data
            //children[priceList].data
            //children[priceList].data[1].priceList
            $i++;
        }
    }

    /**
     * @param PriceListAwareInterface|array $item
     * @return int
     */
    protected function getPriceListId($item)
    {
        if ($item instanceof PriceListAwareInterface) {
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
}
