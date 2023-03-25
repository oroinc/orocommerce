<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Form\DataTransformer\QuantityTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

class QuantityTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $numberFormatter;

    protected function setUp(): void
    {
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
    }

    public function testTransformationSuccess()
    {
        $valueForTransformation = '1000.8666';
        $expectedTransformedValue = '1.000,8666';

        $this->numberFormatter->expects($this->once())
            ->method('formatDecimal')
            ->with($valueForTransformation)
            ->willReturn($expectedTransformedValue);

        $transformer = new QuantityTransformer($this->numberFormatter);

        $actual = $transformer->transform($valueForTransformation);

        self::assertEquals($expectedTransformedValue, $actual);
    }

    public function testReverseTransformationSuccess()
    {
        $valueForReverseTransformation = '1000,8666';
        $expectedTransformedValue = 1000.8666;

        $this->numberFormatter->expects($this->once())
            ->method('parseFormattedDecimal')
            ->with($valueForReverseTransformation)
            ->willReturn($expectedTransformedValue);

        $transformer = new QuantityTransformer($this->numberFormatter);

        $actual = $transformer->reverseTransform($valueForReverseTransformation);

        self::assertSame($expectedTransformedValue, $actual);
    }

    public function testReverseTransformationWillFailWithTransformationExceptionIfDecimalCouldNotBeParsed()
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Quantity 1..000,8666 is not a valid decimal number');

        $valueForReverseTransformation = '1..000,8666';

        $this->numberFormatter->expects($this->once())
            ->method('parseFormattedDecimal')
            ->with($valueForReverseTransformation)
            ->willReturn(false);

        $transformer = new QuantityTransformer($this->numberFormatter);

        $transformer->reverseTransform($valueForReverseTransformation);
    }
}
