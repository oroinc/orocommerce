<?php

namespace OroB2B\Bundle\ShippingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;

class UniqueProductUnitShippingOptionsValidator extends ConstraintValidator
{
    const PRODUCT_UNIT_KEY = 'productUnit';

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var ExecutionContext $context */
        $context = $this->context;

        $ids = [];

        foreach ($value as $index => $item) {
            if (null === $id = $this->getProductUnitId($item)) {
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

    private function getProductUnitId($item)
    {
        if ($item instanceof ProductUnitHolderInterface && $item->getProductUnit()) {
            return $item->getProductUnit()->getCode();
        }
    }

    /**
     * @param mixed $item
     * @param integer $index
     *
     * @return string
     */
    protected function getViolationPath($item, $index)
    {
        if ($item instanceof ProductUnitHolderInterface) {
            return "[$index][" . self::PRODUCT_UNIT_KEY . "]";
        }

        return '';
    }
}
