<?php

namespace Oro\Bundle\ProductBundle\Formatter;

/**
 * Formats unit labels with support for short and plural variations.
 */
class UnitLabelFormatter extends AbstractUnitFormatter implements UnitLabelFormatterInterface
{
    #[\Override]
    public function format(?string $code, bool $isShort = false, bool $isPlural = false): string
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

    #[\Override]
    public function formatChoices(array $units, bool $isShort = false, bool $isPlural = false): array
    {
        $result = [];
        foreach ($units as $unit) {
            $result[$unit->getCode()] = $this->format($unit->getCode(), $isShort, $isPlural);
        }

        return $result;
    }
}
