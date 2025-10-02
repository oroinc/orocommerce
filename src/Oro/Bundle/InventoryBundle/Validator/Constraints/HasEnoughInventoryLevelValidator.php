<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator that checks for a product line item if there is enough quantity in inventory level.
 */
class HasEnoughInventoryLevelValidator extends ConstraintValidator
{
    protected ManagerRegistry $managerRegistry;

    protected InventoryQuantityManager $quantityManager;

    protected UnitLabelFormatterInterface $unitLabelFormatter;

    public function __construct(
        ManagerRegistry $managerRegistry,
        InventoryQuantityManager $quantityManager,
        UnitLabelFormatterInterface $unitLabelFormatter
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->quantityManager = $quantityManager;
        $this->unitLabelFormatter = $unitLabelFormatter;
    }

    /**
     * @param ProductLineItemInterface $value
     * @param HasEnoughInventoryLevel $constraint
     */
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if ($value === null) {
            return;
        }

        if (!$value instanceof ProductHolderInterface) {
            throw new UnexpectedValueException($value, ProductHolderInterface::class);
        }

        if (!$value instanceof ProductUnitHolderInterface) {
            throw new UnexpectedValueException($value, ProductUnitHolderInterface::class);
        }

        if (!$constraint instanceof HasEnoughInventoryLevel) {
            throw new UnexpectedTypeException($constraint, HasEnoughInventoryLevel::class);
        }

        $product = $value->getProduct();
        if ($product === null) {
            return;
        }

        if (!$this->quantityManager->shouldDecrement($product)) {
            return;
        }

        $productUnit = $value->getProductUnit() ?? $product->getPrimaryUnitPrecision()?->getUnit();

        if (!$this->isEnoughQuantity($product, $productUnit, $value->getQuantity())) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ product_sku }}', $this->formatValue($product->getSku()))
                ->setParameter(
                    '{{ unit }}',
                    $this->formatValue($this->unitLabelFormatter->format($productUnit->getCode()))
                )
                ->setParameter('{{ quantity }}', $this->formatValue($value->getQuantity()))
                ->atPath('quantity')
                ->setCause($value)
                ->setCode(HasEnoughInventoryLevel::NOT_ENOUGH_QUANTITY)
                ->addViolation();
        }
    }

    protected function isEnoughQuantity(Product $product, ProductUnit $productUnit, float|int $quantity): bool
    {
        $inventoryLevel = $this->managerRegistry->getRepository(InventoryLevel::class)
            ->getLevelByProductAndProductUnit($product, $productUnit);

        return $inventoryLevel !== null && $this->quantityManager->hasEnoughQuantity($inventoryLevel, $quantity);
    }
}
