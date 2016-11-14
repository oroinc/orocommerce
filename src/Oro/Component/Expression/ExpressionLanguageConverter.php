<?php

namespace Oro\Component\Expression;

use Oro\Component\Expression\Node as ExpressionNode;
use Symfony\Component\ExpressionLanguage\Node;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class ExpressionLanguageConverter
{
    const FIELDS_KEY = 'fields';
    const CONTAINER_ID_KEY = 'container_id';

    /**
     * @var FieldsProviderInterface
     */
    protected $fieldsProvider;

    /**
     * @param FieldsProviderInterface $fieldsProvider
     */
    public function __construct(FieldsProviderInterface $fieldsProvider)
    {
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * @param ParsedExpression $expression
     * @param array $namesMapping
     * @return ExpressionNode\NodeInterface
     */
    public function convert(ParsedExpression $expression, array $namesMapping = [])
    {
        return $this->convertExpressionLanguageNode($expression->getNodes(), $namesMapping);
    }

    /**
     * @param Node\Node $node
     * @param array $namesMapping
     * @return ExpressionNode\NodeInterface
     */
    protected function convertExpressionLanguageNode(Node\Node $node, array $namesMapping = [])
    {
        if ($node instanceof Node\BinaryNode) {
            return $this->convertBinaryNode($node, $namesMapping);
        } elseif ($node instanceof Node\GetAttrNode || $node instanceof Node\NameNode) {
            return $this->convertFieldAwareNode($node, $namesMapping);
        } elseif ($node instanceof Node\ConstantNode) {
            return new ExpressionNode\ValueNode(
                $this->getConstantNodeValue($node)
            );
        } elseif ($node instanceof Node\UnaryNode) {
            return new ExpressionNode\UnaryNode(
                $this->convertExpressionLanguageNode($node->nodes['node'], $namesMapping),
                $this->getOperator($node)
            );
        } elseif ($node instanceof Node\ArrayNode) {
            return $this->convertArrayNode($node);
        }

        throw new SyntaxError(sprintf('Unsupported expression node %s', get_class($node)));
    }

    /**
     * @param Node\GetAttrNode|Node\NameNode|Node\Node $node
     * @param array $namesMapping
     * @return ExpressionNode\NameNode|ExpressionNode\RelationNode
     */
    protected function convertFieldAwareNode(Node\Node $node, array $namesMapping = [])
    {
        $metadata = [
            self::FIELDS_KEY => [],
            self::CONTAINER_ID_KEY => null
        ];
        $this->getFieldAwareNodeMetadata($node, $metadata, $namesMapping);
        $metadata[self::FIELDS_KEY] = array_reverse($metadata[self::FIELDS_KEY]);
        $fieldsCount = count($metadata[self::FIELDS_KEY]);

        if ($fieldsCount > 3) {
            throw new SyntaxError('Relations of related entities are not allowed to be used');
        }

        // Add entity identifier as field for container
        if ($fieldsCount === 1) {
            $metadata[self::FIELDS_KEY][] = $this->fieldsProvider->getIdentityFieldName(
                $metadata[self::FIELDS_KEY][0]
            );
            $fieldsCount++;
        }
        // Add entity identifier as field for relations
        if ($fieldsCount === 2) {
            if ($this->fieldsProvider->isRelation($metadata[self::FIELDS_KEY][0], $metadata[self::FIELDS_KEY][1])) {
                $metadata[self::FIELDS_KEY][] = $this->fieldsProvider->getIdentityFieldName(
                    $this->fieldsProvider->getRealClassName(
                        $metadata[self::FIELDS_KEY][0],
                        $metadata[self::FIELDS_KEY][1]
                    )
                );
                $fieldsCount++;
            }
        }

        if ($fieldsCount === 3) {
            return new ExpressionNode\RelationNode(
                $metadata[self::FIELDS_KEY][0],
                $metadata[self::FIELDS_KEY][1],
                $metadata[self::FIELDS_KEY][2],
                $metadata[self::CONTAINER_ID_KEY]
            );
        } else {
            return new ExpressionNode\NameNode(
                $metadata[self::FIELDS_KEY][0],
                $metadata[self::FIELDS_KEY][1],
                $metadata[self::CONTAINER_ID_KEY]
            );
        }
    }

    /**
     * @param Node\Node $node
     * @param array $metadata
     * @param array $namesMapping
     * @return array
     */
    protected function getFieldAwareNodeMetadata(Node\Node $node, array &$metadata, array $namesMapping = [])
    {
        if ($node instanceof Node\GetAttrNode) {
            if ($this->getNodeType($node) === Node\GetAttrNode::PROPERTY_CALL) {
                $metadata[self::FIELDS_KEY][] = $this->getConstantNodeValue($node->nodes['attribute']);
                $this->getFieldAwareNodeMetadata($node->nodes['node'], $metadata, $namesMapping);
            } elseif ($this->getNodeType($node) === Node\GetAttrNode::ARRAY_CALL) {
                $metadata[self::CONTAINER_ID_KEY] = $this->getConstantNodeValue($node->nodes['attribute']);
                if (!$node->nodes['node'] instanceof Node\NameNode) {
                    throw new SyntaxError('Attribute is supported only for root variable in expression');
                }
                $this->getFieldAwareNodeMetadata($node->nodes['node'], $metadata, $namesMapping);
            } else {
                throw new SyntaxError('Function calls are not supported');
            }
        } elseif ($node instanceof Node\NameNode) {
            $metadata[self::FIELDS_KEY][] = $this->getNameNodeValue($node, $namesMapping);
        }

        return $metadata;
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

    /**
     * @param Node\Node $node
     * @return int
     */
    protected function getNodeType(Node\Node $node)
    {
        return $node->attributes['type'];
    }

    /**
     * @param Node\ArrayNode $node
     * @return ExpressionNode\ValueNode
     */
    protected function convertArrayNode(Node\ArrayNode $node)
    {
        $value = [];
        foreach (array_chunk($node->nodes, 2) as $pair) {
            if (!$pair[0] instanceof Node\ConstantNode || !$pair[1] instanceof Node\ConstantNode) {
                throw new SyntaxError('Only constant are supported for arrays');
            }
            $value[$this->getConstantNodeValue($pair[0])] = $this->getConstantNodeValue($pair[1]);
        }

        return new ExpressionNode\ValueNode($value);
    }

    /**
     * @param Node\Node $node
     * @return string
     */
    protected function getOperator(Node\Node $node)
    {
        return $node->attributes['operator'];
    }

    /**
     * @param Node\BinaryNode $node
     * @param array $namesMapping
     * @return ExpressionNode\BinaryNode
     */
    protected function convertBinaryNode(Node\BinaryNode $node, array $namesMapping)
    {
        $left = $this->convertExpressionLanguageNode($node->nodes['left'], $namesMapping);
        $right = $this->convertExpressionLanguageNode($node->nodes['right'], $namesMapping);
        $operator = $this->getOperator($node);

        if ($operator === 'in' || $operator === 'not in') {
            if (!$left instanceof ExpressionNode\ContainerHolderNodeInterface) {
                throw new SyntaxError(sprintf('Left operand of %s must be field expression', $operator));
            }
            if ((!$right instanceof ExpressionNode\ContainerHolderNodeInterface
                    && !$right instanceof ExpressionNode\ValueNode)
                || ($right instanceof ExpressionNode\ValueNode && !is_array($right->getValue()))
            ) {
                throw new SyntaxError(
                    sprintf('Right operand of %s must be an array of scalars or field expression', $operator)
                );
            }
        }

        return new ExpressionNode\BinaryNode($left, $right, $operator);
    }
}
