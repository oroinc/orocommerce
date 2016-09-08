<?php

namespace Oro\Bundle\WarehouseBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseBundle\SystemConfig\WarehouseConfig;

class UniqueWarehouseValidator extends ConstraintValidator
{
    const WAREHOUSE_KEY = 'warehouse';

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var UniqueWarehouse $constraint */

        /** @var ExecutionContext $context */
        $context = $this->context;

        $ids = [];
        foreach ($value as $index => $item) {
            $id = $this->getWarehouseId($item);
            if (null === $id) {
                continue;
            }
            if (in_array($id, $ids, true)) {
                $path = $this->getViolationPath($item, $index);
                $context->buildViolation($constraint->getMessage(), [])
                    ->atPath($path)
                    ->addViolation();
            }
            $ids[] = $id;
        }
    }

    /**
     * @param mixed $item
     * @return int|null
     */
    protected function getWarehouseId($item)
    {
        if ($item instanceof WarehouseConfig && $item->getWarehouse()) {
            return $item->getWarehouse()->getId();
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
        if ($item instanceof WarehouseConfig) {
            return "[$index]." . self::WAREHOUSE_KEY;
        }

        return '';
    }
}
