<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Formatter;

use Oro\Bundle\ProductBundle\Entity\MeasureUnitInterface;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class UnitLabelFormatterTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    protected $translator;

    /** @var UnitLabelFormatterInterface */
    protected $formatter;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->formatter = $this->createFormatter();
    }

    /**
     * @dataProvider formatProvider
     */
    public function testFormat(string $unitCode, bool $isShort, bool $isPlural, string $expected)
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($expected);
        $this->formatter->format($unitCode, $isShort, $isPlural);
    }

    public function formatProvider(): array
    {
        return [
            'format full single' => [
                'unitCode'  => 'kg',
                'isShort'   => false,
                'isPlural'  => false,
                'expected'  => $this->getTranslationPrefix() . '.kg.label.full',
            ],
            'format full plural' => [
                'unitCode'  => 'kg',
                'isShort'   => false,
                'isPlural'  => true,
                'expected'  => $this->getTranslationPrefix() . '.kg.label.full_plural',
            ],
            'format short single' => [
                'unitCode'  => 'item',
                'isShort'   => true,
                'isPlural'   => false,
                'expected'  => $this->getTranslationPrefix() . '.item.label.short',
            ],
            'format short plural' => [
                'unitCode'  => 'item',
                'isShort'   => true,
                'isPlural'  => true,
                'expected'  => $this->getTranslationPrefix() . '.item.label.short_plural',
            ],
            'empty code' => [
                'unitCode'  => '',
                'isShort'   => true,
                'isPlural'  => true,
                'expected'  => 'N/A',
            ],
        ];
    }

    /**
     * @dataProvider formatChoicesProvider
     */
    public function testFormatChoices(bool $isShort, bool $isPlural, array $expected)
    {
        $units = [$this->createObject('kg'), $this->createObject('item')];

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                [$this->getTranslationPrefix() . '.kg.label.full', [], null, null, '_KG'],
                [$this->getTranslationPrefix() . '.kg.label.full_plural', [], null, null, '_KG_PLURAL'],
                [$this->getTranslationPrefix() . '.item.label.full', [], null, null, '_ITEM'],
                [$this->getTranslationPrefix() . '.item.label.full_plural', [], null, null, '_ITEM_PLURAL'],
                [$this->getTranslationPrefix() . '.kg.label.short', [], null, null, '_KG_SHORT'],
                [$this->getTranslationPrefix() . '.kg.label.short_plural', [], null, null, '_KG_SHORT_PLURAL'],
                [$this->getTranslationPrefix() . '.item.label.short', [], null, null, '_ITEM_SHORT'],
                [$this->getTranslationPrefix() . '.item.label.short_plural', [], null, null, '_ITEM_SHORT_PLURAL']
            ]);

        $this->assertEquals($expected, $this->formatter->formatChoices($units, $isShort, $isPlural));
    }

    public function formatChoicesProvider(): array
    {
        return [
            'format choices full single' => [
                'isShort' => false,
                'isPlural' => false,
                'expected' => [
                    'kg' => '_KG',
                    'item' => '_ITEM'
                ],
            ],
            'format choices full plural' => [
                'isShort' => false,
                'isPlural' => true,
                'expected' => [
                    'kg' => '_KG_PLURAL',
                    'item' => '_ITEM_PLURAL'
                ],
            ],
            'format choices short single' => [
                'isShort' => true,
                'isPlural' => false,
                'expected' => [
                    'kg' => '_KG_SHORT',
                    'item' => '_ITEM_SHORT'
                ],
            ],
            'format choices short plural' => [
                'isShort' => true,
                'isPlural' => true,
                'expected' => [
                    'kg' => '_KG_SHORT_PLURAL',
                    'item' => '_ITEM_SHORT_PLURAL'
                ],
            ],
        ];
    }

    abstract protected function createFormatter(): UnitLabelFormatterInterface;

    abstract protected function getTranslationPrefix(): string;

    abstract protected function createObject(string $code): MeasureUnitInterface;
}
