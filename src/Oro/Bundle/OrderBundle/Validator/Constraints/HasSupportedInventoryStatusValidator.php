<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Constraint validator that checks for a product if it has supported inventory status.
 */
class HasSupportedInventoryStatusValidator extends ConstraintValidator
{
    protected ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param Product $value
     * @param HasSupportedInventoryStatus $constraint
     */
    #[\Override]
    public function validate($value, Constraint $constraint): void
    {
        if ($value === null) {
            return;
        }

        if (!$constraint instanceof HasSupportedInventoryStatus) {
            throw new UnexpectedTypeException($constraint, HasSupportedInventoryStatus::class);
        }

        if (!$value instanceof Product) {
            throw new UnexpectedValueException($value, Product::class);
        }

        $supportedInventoryStatuses = $this->configManager->get($constraint->configurationPath) ?? [];

        if (!in_array($value->getInventoryStatus()->getId(), $supportedInventoryStatuses, true)) {
            $this->context
                ->buildViolation($constraint->message)
                ->setCause($value)
                ->addViolation();
        }
    }
}
