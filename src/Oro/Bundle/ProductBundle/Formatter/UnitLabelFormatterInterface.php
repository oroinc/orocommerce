<?php

namespace Oro\Bundle\ProductBundle\Formatter;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;

interface UnitLabelFormatterInterface
{
    /**
     * @param string $code
     * @param bool $isShort
     * @param bool $isPlural
     *
     * @return string
     */
    public function format($code, $isShort = false, $isPlural = false);

    /**
     * @param array|MeasureUnitInterface[] $units
     * @param bool $isShort
     * @param bool $isPlural
     *
     * @return array
     */
    public function formatChoices(array $units, $isShort = false, $isPlural = false);
}
