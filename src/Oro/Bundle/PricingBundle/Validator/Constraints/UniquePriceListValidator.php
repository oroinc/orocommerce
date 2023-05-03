<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListAwareInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator checks that there are no duplicated price lists.
 */
class UniquePriceListValidator extends ConstraintValidator
{
    private const PRICE_LIST_KEY = 'priceList';

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniquePriceList) {
            throw new UnexpectedTypeException($constraint, UniquePriceList::class);
        }

        if (!\is_array($value)) {
            return;
        }

        $ids = [];
        foreach ($value as $index => $item) {
            $id = $this->getPriceListId($item);
            if (null === $id) {
                continue;
            }
            if (\in_array($id, $ids, true)) {
                $this->context->buildViolation($constraint->message, [])
                    ->atPath($this->getViolationPath($item, $index))
                    ->addViolation();
            }
            $ids[] = $id;
        }
    }

    private function getPriceListId(mixed $item): ?int
    {
        if ($item instanceof PriceListAwareInterface && $item->getPriceList()) {
            return $item->getPriceList()->getId();
        }
        if (\is_array($item)
            && \array_key_exists(self::PRICE_LIST_KEY, $item)
            && $item[self::PRICE_LIST_KEY] instanceof PriceList
        ) {
            return $item[self::PRICE_LIST_KEY]->getId();
        }

        return null;
    }

    private function getViolationPath(mixed $item, int $index): string
    {
        if ($item instanceof PriceListAwareInterface) {
            return "[$index]." . self::PRICE_LIST_KEY;
        }
        if (\is_array($item) && \array_key_exists(self::PRICE_LIST_KEY, $item)) {
            return "[$index][" . self::PRICE_LIST_KEY . ']';
        }

        return '';
    }
}
