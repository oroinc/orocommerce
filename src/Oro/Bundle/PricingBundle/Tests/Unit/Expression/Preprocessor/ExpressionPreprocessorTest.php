<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Expression\Preprocessor;

use Oro\Bundle\PricingBundle\Expression\Preprocessor\ExpressionPreprocessor;
use Oro\Bundle\PricingBundle\Expression\Preprocessor\ExpressionPreprocessorInterface;

class ExpressionPreprocessorTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessMaxIterationsExceeded()
    {
        $preprocessor = $this->getMock(ExpressionPreprocessorInterface::class);
        $preprocessor->expects($this->any())
            ->method('process')
            ->willReturnCallback(
                function ($expression) {
                    return $expression . '+';
                }
            );

        $this->setExpectedException(\RuntimeException::class, 'Max iterations count 100 exceed');

        $expressionPreprocessor = new ExpressionPreprocessor();
        $expressionPreprocessor->registerPreprocessor($preprocessor);
        $expressionPreprocessor->process('a');
    }

    public function testProcess()
    {
        $preprocessor = $this->getMock(ExpressionPreprocessorInterface::class);
        $preprocessor->expects($this->exactly(2))
            ->method('process')
            ->willReturn('processed');

        $expressionPreprocessor = new ExpressionPreprocessor();
        $expressionPreprocessor->registerPreprocessor($preprocessor);
        $this->assertEquals('processed', $expressionPreprocessor->process('unprocessed'));
    }
}
