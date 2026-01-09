<?php

namespace Oro\Bundle\ProductBundle\Formatter;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

/**
 * Defines how unit labels are formatted.
 */
interface UnitLabelFormatterInterface
{
    public function format(?string $code, bool $isShort = false, bool $isPlural = false): string;

    /**
     * @param array|MeasureUnitInterface[] $units
     * @param bool $isShort
     * @param bool $isPlural
     *
     * @return array
     */
    public function formatChoices(array $units, bool $isShort = false, bool $isPlural = false): array;
}
