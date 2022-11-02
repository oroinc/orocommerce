<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\VariantFieldValueHandler;

use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\BooleanVariantFieldValueHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

class BooleanVariantFieldValueHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var BooleanVariantFieldValueHandler */
    private $handler;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnMap([
                ['oro.product.variant_fields.no.label', [], null, null, 'No'],
                ['oro.product.variant_fields.yes.label', [], null, null, 'Yes'],
            ]);

        $this->handler = new BooleanVariantFieldValueHandler($translator);
    }

    public function testGetType()
    {
        $this->assertEquals(BooleanVariantFieldValueHandler::TYPE, $this->handler->getType());
    }

    public function testGetValues()
    {
        $this->assertEquals([0 => 'No', 1 => 'Yes'], $this->handler->getPossibleValues('testField'));
    }

    /**
     * @dataProvider getScalarValueProvider
     */
    public function testGetScalarValue(mixed $value, bool $expected)
    {
        $this->assertSame($expected, $this->handler->getScalarValue($value));
    }

    /**
     * @dataProvider getHumanReadableValueProvider
     */
    public function testGetHumanReadableValue(mixed $value, string $expected)
    {
        $this->assertEquals($expected, $this->handler->getHumanReadableValue('any_value', $value));
    }

    public function getScalarValueProvider(): array
    {
        return [
            'return false' => [
                'value' => 0,
                'expected' => false
            ],
            'return true' => [
                'value' => 1,
                'expected' => true
            ]
        ];
    }

    public function getHumanReadableValueProvider(): array
    {
        return [
            'return human readable false' => [
                'value' => 0,
                'expected' => 'No'
            ],
            'return human readable true' => [
                'value' => 1,
                'expected' => 'Yes'
            ]
        ];
    }
}
