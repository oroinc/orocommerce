<?php

namespace OroB2B\Bundle\PricingBundle\Model;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionParser
{
    protected $allowedNames = ['Product', 'Category'];

    public function parse($expression)
    {
        $expression = str_replace('%', '/100', $expression);

        $language = new ExpressionLanguage();
        $parsedExpression = $language->parse($expression, $this->allowedNames);
    }

    /**
     * @param $rule
     * @return array
     */
    public function getUsedLexemes($rule)
    {
        //TODO: Parse lexems
        return [$rule];
    }
}
