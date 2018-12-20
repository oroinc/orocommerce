<?php

namespace Oro\Bundle\RuleBundle\Tests\Unit\ExpressionLanguage;

use Oro\Bundle\RuleBundle\ExpressionLanguage\CountExpressionLanguageFunction;

class CountExpressionLanguageFunctionTest extends \PHPUnit\Framework\TestCase
{
    public function testFunction()
    {
        $function = new CountExpressionLanguageFunction();

        $expected = [
            CountExpressionLanguageFunction::FUNCTION_NAME,
            function ($field) {
                return sprintf('%s(%s)', CountExpressionLanguageFunction::FUNCTION_NAME, $field);
            },
            function ($arguments, $field) {
                return count($field);
            },
        ];

        $actual = [
            $function->getName(),
            $function->getCompiler(),
            $function->getEvaluator()
        ];

        $this->assertEquals($expected, $actual);
    }
}
