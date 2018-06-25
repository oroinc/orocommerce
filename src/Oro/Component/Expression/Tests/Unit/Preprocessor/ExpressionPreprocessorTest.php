<?php

namespace Oro\Component\Expression\Tests\Unit\Preprocessor;

use Oro\Component\Expression\Preprocessor\ExpressionPreprocessor;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;

class ExpressionPreprocessorTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessMaxIterationsExceeded()
    {
        $preprocessor = $this->createMock(ExpressionPreprocessorInterface::class);
        $preprocessor->expects($this->any())
            ->method('process')
            ->willReturnCallback(
                function ($expression) {
                    return $expression . '+';
                }
            );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Max iterations count 100 exceed');

        $expressionPreprocessor = new ExpressionPreprocessor();
        $expressionPreprocessor->registerPreprocessor($preprocessor);
        $expressionPreprocessor->process('a');
    }

    public function testProcess()
    {
        $preprocessor = $this->createMock(ExpressionPreprocessorInterface::class);
        $preprocessor->expects($this->exactly(2))
            ->method('process')
            ->willReturn('processed');

        $expressionPreprocessor = new ExpressionPreprocessor();
        $expressionPreprocessor->registerPreprocessor($preprocessor);
        $this->assertEquals('processed', $expressionPreprocessor->process('unprocessed'));
    }
}
