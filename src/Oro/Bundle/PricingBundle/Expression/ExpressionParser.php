<?php

namespace Oro\Bundle\PricingBundle\Expression;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ExpressionParser
{
    /**
     * @var array
     */
    protected $namesMapping = [];

    /**
     * @var array
     */
    protected $expressionMappings = [];

    /**
     * @var ExpressionLanguageConverter
     */
    protected $converter;

    /**
     * @var array
     */
    protected $expressionCache = [];

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
     * @return NodeInterface|null
     */
    public function parse($expression)
    {
        if (!$expression) {
            return null;
        }

        $cacheKey = md5($expression);
        if (array_key_exists($cacheKey, $this->expressionCache)) {
            return $this->expressionCache[$cacheKey];
        }

        foreach ($this->expressionMappings as $search => $replace) {
            $expression = str_ireplace($search, $replace, $expression);
        }

        $language = new ExpressionLanguage();
        $parsedLanguageExpression = $language->parse($expression, $this->getSupportedNames());
        
        $nodes = $this->converter->convert($parsedLanguageExpression, $this->namesMapping);
        $this->expressionCache[$cacheKey] = $nodes;

        return $nodes;
    }

    /**
     * @param string|Expression $expression
     * @return array
     */
    public function getUsedLexemes($expression)
    {
        $usedLexemes = [];
        $rootNode = $this->parse($expression);
        if (!$rootNode) {
            return $usedLexemes;
        }

        foreach ($rootNode->getNodes() as $node) {
            if ($node instanceof NameNode) {
                $class = $node->getContainer();
                if (!array_key_exists($class, $usedLexemes)) {
                    $usedLexemes[$class] = [];
                }
                if (!in_array($node->getField(), $usedLexemes[$class], true)) {
                    $usedLexemes[$class][] = $node->getField();
                }
            } elseif ($node instanceof RelationNode) {
                $class = $node->getRelationAlias();
                if (!array_key_exists($class, $usedLexemes)) {
                    $usedLexemes[$class] = [];
                }
                if (!in_array($node->getRelationField(), $usedLexemes[$class], true)) {
                    $usedLexemes[$class][] = $node->getRelationField();
                }
            }
        }
        
        return $usedLexemes;
    }

    /**
     * @param string|Expression $expression
     * @return array
     */
    public function getUsedLexemesConsideringContainerId($expression)
    {
        $usedLexemes = [];
        $rootNode = $this->parse($expression);
        if (!$rootNode) {
            return $usedLexemes;
        }

        foreach ($rootNode->getNodes() as $node) {
            if ($node instanceof NameNode) {
                $class = $node->getContainer();
                $containerId = $node->getContainerId();

                if (!isset($usedLexemes[$class][$containerId]) ||
                    isset($usedLexemes[$class][$containerId])
                    && !in_array($node->getField(), $usedLexemes[$class][$containerId], true)
                ) {
                    $usedLexemes[$class][$containerId][] = $node->getField();
                }
            } elseif ($node instanceof RelationNode) {
                $class = $node->getRelationAlias();
                $containerId = $node->getContainerId();

                if (!isset($usedLexemes[$class][$containerId]) ||
                    isset($usedLexemes[$class][$containerId])
                    && !in_array($node->getRelationField(), $usedLexemes[$class][$containerId], true)
                ) {
                    $usedLexemes[$class][$containerId][] = $node->getRelationField();
                }
            }
        }

        return $usedLexemes;
    }

    /**
     * @return array
     */
    public function getSupportedNames()
    {
        return array_keys($this->namesMapping);
    }

    /**
     * @return array
     */
    public function getReverseNameMapping()
    {
        return array_flip($this->namesMapping);
    }

    /**
     * @return array
     */
    public function getNamesMapping()
    {
        return $this->namesMapping;
    }
}
