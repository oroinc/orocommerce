<?php

namespace OroB2B\Bundle\PricingBundle\Expression;

use Symfony\Component\ExpressionLanguage\Node;
use Symfony\Component\ExpressionLanguage\ParsedExpression;

use OroB2B\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;

class ExpressionLanguageConverter
{
    /**
     * @var PriceRuleFieldsProvider
     */
    protected $fieldsProvider;

    /**
     * @param PriceRuleFieldsProvider $fieldsProvider
     */
    public function __construct(PriceRuleFieldsProvider $fieldsProvider)
    {
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * @param ParsedExpression $expression
     * @param array $namesMapping
     * @return NodeInterface
     */
    public function convert(ParsedExpression $expression, array $namesMapping = [])
    {
        return $this->convertExpressionLanguageNode($expression->getNodes(), $namesMapping);
    }

    /**
     * @param Node\Node $node
     * @param array $namesMapping
     * @return NodeInterface
     */
    protected function convertExpressionLanguageNode(Node\Node $node, array $namesMapping = [])
    {
        if ($node instanceof Node\BinaryNode) {
            return new BinaryNode(
                $this->convertExpressionLanguageNode($node->nodes['left'], $namesMapping),
                $this->convertExpressionLanguageNode($node->nodes['right'], $namesMapping),
                $node->attributes['operator']
            );
        } elseif ($node instanceof Node\GetAttrNode) {
            $rootNameNode = $node->nodes['node'];
            if ($rootNameNode instanceof Node\GetAttrNode) {
                return new RelationNode(
                    $this->getNameNodeValue($rootNameNode->nodes['node'], $namesMapping),
                    $this->getConstantNodeValue($rootNameNode->nodes['attribute']),
                    $this->getConstantNodeValue($node->nodes['attribute'])
                );
            } else {
                $container = $this->getNameNodeValue($rootNameNode, $namesMapping);
                $field = $this->getConstantNodeValue($node->nodes['attribute']);

                if ($this->fieldsProvider->isRelation($container, $field)) {
                    return new RelationNode(
                        $container,
                        $field,
                        $this->fieldsProvider->getIdentityFieldName(
                            $this->fieldsProvider->getRealClassName($container, $field)
                        )
                    );
                } else {
                    return new NameNode($container, $field);
                }
            }
        } elseif ($node instanceof Node\NameNode) {
            $container = $this->getNameNodeValue($node, $namesMapping);
            return new NameNode(
                $container,
                $this->fieldsProvider->getIdentityFieldName($container)
            );
        } elseif ($node instanceof Node\ConstantNode) {
            return new ValueNode(
                $this->getConstantNodeValue($node)
            );
        } elseif ($node instanceof Node\UnaryNode) {
            return new UnaryNode(
                $this->convertExpressionLanguageNode($node->nodes['node'], $namesMapping),
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
     * @param array $namesMapping
     * @return string
     */
    protected function getNameNodeValue(Node\Node $node, array $namesMapping = [])
    {
        $name = $node->attributes['name'];

        if (array_key_exists($name, $namesMapping)) {
            $name = $namesMapping[$name];
        }

        return $name;
    }
}
