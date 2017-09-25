<?php

namespace Oro\Bundle\ProductBundle\Formatter;

class UnitLabelFormatter extends AbstractUnitFormatter implements UnitLabelFormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format($code, $isShort = false, $isPlural = false)
    {
        if (!$code) {
            return $this->translator->trans('N/A');
        }

        return $this->translator->trans(
            sprintf(
                '%s.%s.label.%s%s',
                $this->getTranslationPrefix(),
                $code,
                $isShort ? 'short' : 'full',
                $isPlural ? '_plural' : ''
            )
        );
    }

    /**
     * {@inheritdoc}
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
