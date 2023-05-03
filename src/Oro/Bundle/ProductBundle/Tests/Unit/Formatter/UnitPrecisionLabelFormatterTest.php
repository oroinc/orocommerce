<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Formatter;

use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitPrecisionLabelFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class UnitPrecisionLabelFormatterTest extends TestCase
{
    private TranslatorInterface|MockObject $translator;

    private UnitLabelFormatterInterface|MockObject $unitLabelFormatter;

    private UnitPrecisionLabelFormatter $formatter;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->unitLabelFormatter = $this->createMock(UnitLabelFormatterInterface::class);

        $this->formatter = new UnitPrecisionLabelFormatter($this->unitLabelFormatter, $this->translator);
    }

    public function testFormatUnitPrecisionLabel(): void
    {
        $unitCode = 'item';
        $isShort = true;
        $precision = 2;
        $unitCodeFormatted = 'Item';

        $this->unitLabelFormatter
            ->expects(self::once())
            ->method('format')
            ->with($unitCode, $isShort)
            ->willReturn($unitCodeFormatted);

        $expected = 'item (fractional, 2 decimal digits)';

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with(
                'oro.product.productunitprecision.representation',
                ['{{ label }}' => $unitCodeFormatted, '%count%' => $precision]
            )
            ->willReturn($expected);

        self::assertEquals($expected, $this->formatter->formatUnitPrecisionLabel($unitCode, $precision, $isShort));
    }
}
