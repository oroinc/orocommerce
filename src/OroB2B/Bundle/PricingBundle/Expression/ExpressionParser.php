<?php

namespace OroB2B\Bundle\PricingBundle\Expression;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionParser
{
    /**
     * @var array
     */
    protected $namesMapping = [
        'Category' => 'OroB2B\Bundle\CatalogBundle\Entity\Category',
        'Product' => 'OroB2B\Bundle\ProductBundle\Entity\Product',
        'PriceList' => 'OroB2B\Bundle\PricingBundle\Entity\PriceList',
    ];

    /**
     * @var array
     */
    protected $expressionMappings = [
        '%' => '/100',
        'product.category' => 'Category',
        'PriceList.price.currency' => 'PriceList.currency',
        'PriceList.price.value' => 'PriceList.value',
        'PriceList.price' => 'PriceList.value',
    ];

    /**
     * @var ExpressionLanguageConverter
     */
    protected $converter;

    /**
     * @param ExpressionLanguageConverter $converter
     */
    public function __construct(ExpressionLanguageConverter $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function addNameMapping($key, $value)
    {
        $this->namesMapping[$key] = $value;
    }

    /**
     * @param string $search
     * @param string $replace
     */
    public function addExpressionMapping($search, $replace)
    {
        $this->expressionMappings[$search] = $replace;
    }

    /**
     * @param string|Expression $expression
     * @return ParsedExpression
     */
    public function parse($expression)
    {
        foreach ($this->expressionMappings as $search => $replace) {
            $expression = str_ireplace($search, $replace, $expression);
        }

        $language = new ExpressionLanguage();
        $parsedExpression = $language->parse($expression, $this->getSupportedNames());
        
        return $this->converter->convert($parsedExpression);
    }

    /**
     * @param string|Expression $expression
     * @return ParsedExpression
     */
    public function getUsedLexems($expression)
    {
        $usedLexems = [];
        $rootNode = $this->parse($expression);
        foreach ($rootNode->getNodes() as $node) {
            if ($node instanceof NameNode) {
                $class = $this->namesMapping[$node->getContainer()];
                if (!array_key_exists($class, $usedLexems)) {
                    $usedLexems[$class] = [];
                }
                if (!in_array($node->getField(), $usedLexems[$class], true)) {
                    $usedLexems[$class][] = $node->getField();
                }
            }
        }
        
        return $usedLexems;
    }

    /**
     * @return array
     */
    protected function getSupportedNames()
    {
        return array_keys($this->namesMapping);
    }
}
