<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\QuickAdd;

use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Maps constraint violation errors to each {@see QuickAddRow} of {@see QuickAddRowCollection}.
 */
class QuickAddRowCollectionViolationsMapper
{
    /**
     * @param iterable<ConstraintViolationInterface> $constraintViolations
     */
    public function mapViolations(QuickAddRowCollection $quickAddRowCollection, iterable $constraintViolations): void
    {
        foreach ($constraintViolations as $violation) {
            [$index, $propertyName] = $this->extractFromPropertyPath($violation->getPropertyPath());

            if ($index === null) {
                $quickAddRowCollection->addError(
                    $violation->getMessageTemplate() ?: $violation->getMessage(),
                    $violation->getParameters()
                );
                continue;
            }

            /** @var QuickAddRow|null $quickAddRow */
            $quickAddRow = $quickAddRowCollection[$index] ?? null;
            if ($quickAddRow !== null) {
                $quickAddRow->addError(
                    $violation->getMessageTemplate() ?: $violation->getMessage(),
                    $violation->getParameters(),
                    $propertyName
                );
            }
        }
    }

    /**
     * @param string|null $path
     * @return array{?int, ?string}
     */
    private function extractFromPropertyPath(?string $path): array
    {
        if ((string) $path === '') {
            return [null, null];
        }

        $propertyPath = new PropertyPath($path);
        $index = null;
        $propertyName = null;
        foreach ($propertyPath as $i => $element) {
            if ($propertyPath->isIndex($i)) {
                $index = (int)$element;
                continue;
            }
            if ($index !== null) {
                $propertyName = (string)$element;
                break;
            }
        }

        return [$index, $propertyName];
    }
}
