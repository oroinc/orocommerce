<?php

namespace OroB2B\Bundle\PricingBundle\Expression;

use Symfony\Component\ExpressionLanguage\Node;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

class ExpressionLanguageConverter
{
    /**
     * @param ParsedExpression $expression
     * @return BinaryNode|NameNode|ValueNode
     */
    public function convert(ParsedExpression $expression)
    {
        return $this->convertExpressionLanguageNode($expression->getNodes());
    }

    /**
     * @param Node\Node $node
     * @return BinaryNode|NameNode|ValueNode
     */
    protected function convertExpressionLanguageNode(Node\Node $node)
    {
        if ($node instanceof Node\BinaryNode) {
            return new BinaryNode(
                $this->convertExpressionLanguageNode($node->nodes['left']),
                $this->convertExpressionLanguageNode($node->nodes['right']),
                $node->attributes['operator']
            );
        } elseif ($node instanceof Node\GetAttrNode) {
            return new NameNode(
                $this->getNameNodeValue($node->nodes['node']),
                $this->getConstantNodeValue($node->nodes['attribute'])
            );
        } elseif ($node instanceof Node\NameNode) {
            return new NameNode(
                $this->getNameNodeValue($node)
            );
        } elseif ($node instanceof Node\ConstantNode) {
            return new ValueNode(
                $this->getConstantNodeValue($node)
            );
        } elseif ($node instanceof Node\UnaryNode) {
            return new UnaryNode(
                $this->convertExpressionLanguageNode($node->nodes['node']),
                $node->attributes['operator']
            );
        }

        throw new \RuntimeException(sprintf('Unsupported expression node %s', get_class($node)));
    }

    /**
     * @param Node\Node $node
     * @return mixed
     */
    protected function getConstantNodeValue(Node\Node $node)
    {
        return $node->attributes['value'];
    }

    /**
     * @param Node\Node $node
     * @return string
     */
    protected function getNameNodeValue(Node\Node $node)
    {
        return $node->attributes['name'];
    }
}
