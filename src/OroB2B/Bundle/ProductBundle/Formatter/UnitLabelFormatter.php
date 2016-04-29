<?php

namespace OroB2B\Bundle\ProductBundle\Formatter;

use OroB2B\Bundle\ProductBundle\Entity\MeasureUnitInterface;

class UnitLabelFormatter extends AbstractUnitFormatter
{
    /**
     * @param string $code
     * @param bool $isShort
     * @param bool $isPlural
     *
     * @return string
     */
    public function format($code, $isShort = false, $isPlural = false)
    {
        $this->assertTranslationPrefix();

        return $this->translator->trans(
            sprintf(
                '%s.%s.label.%s%s',
                $this->translationPrefix,
                $code,
                $isShort ? 'short' : 'full',
                $isPlural ? '_plural' : ''
            )
        );
    }

    /**
     * @param array|MeasureUnitInterface[] $units
     * @param bool $isShort
     * @param bool $isPlural
     *
     * @return array
     */
    public function formatChoices(array $units, $isShort = false, $isPlural = false)
    {
        $result = [];
        foreach ($units as $unit) {
            $result[$unit->getCode()] = $this->format($unit->getCode(), $isShort, $isPlural);
        }

        return $result;
    }
}
