<?php

namespace Oro\Bundle\ProductBundle\Formatter;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides formatted product unit and precision.
 */
class UnitPrecisionLabelFormatter
{
    private TranslatorInterface $translator;

    private UnitLabelFormatterInterface $unitLabelFormatter;

    public function __construct(UnitLabelFormatterInterface $unitLabelFormatter, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->unitLabelFormatter = $unitLabelFormatter;
    }

    public function formatUnitPrecisionLabel(string $unitCode, int $precision, bool $isShort = false): string
    {
        return $this->translator->trans(
            'oro.product.productunitprecision.representation',
            ['{{ label }}' => $this->unitLabelFormatter->format($unitCode, $isShort), '%count%' => $precision]
        );
    }
}
