<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Oro\Component\ExpressionLanguage\Node;

use Doctrine\Common\Inflector\Inflector;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\ExpressionLanguage\Compiler;
use Symfony\Component\ExpressionLanguage\Node\ArrayNode;
use Symfony\Component\ExpressionLanguage\Node\NameNode;
use Symfony\Component\ExpressionLanguage\Node\Node;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
class GetAttrNode extends Node
{
    const PROPERTY_CALL = 1;
    const ARRAY_CALL = 2;
    const ALL_CALL = 3;
    const ANY_CALL = 4;

    /**
     * @var PropertyAccessor
     */
    protected static $propertyAccessor;

    public function __construct(Node $node, Node $attribute, ArrayNode $arguments, $type)
    {
        parent::__construct(
            array('node' => $node, 'attribute' => $attribute, 'arguments' => $arguments),
            array('type' => $type)
        );
    }

    public function compile(Compiler $compiler)
    {
        switch ($this->attributes['type']) {
            case self::PROPERTY_CALL:
                $compiler
                    ->compile($this->nodes['node'])
                    ->raw('->')
                    ->raw($this->nodes['attribute']->attributes['value'])
                ;
                break;

            case self::METHOD_CALL:
                $compiler
                    ->compile($this->nodes['node'])
                    ->raw('->')
                    ->raw($this->nodes['attribute']->attributes['value'])
                    ->raw('(')
                    ->compile($this->nodes['arguments'])
                    ->raw(')')
                ;
                break;

            case self::ARRAY_CALL:
                $compiler
                    ->compile($this->nodes['node'])
                    ->raw('[')
                    ->compile($this->nodes['attribute'])->raw(']')
                ;
                break;
        }
    }

    public function evaluate($functions, $values)
    {
        switch ($this->attributes['type']) {
            case self::PROPERTY_CALL:
                $obj = $this->nodes['node']->evaluate($functions, $values);
                if (!is_object($obj)) {
                    throw new \RuntimeException('Unable to get a property on a non-object.');
                }

                $property = $this->nodes['attribute']->attributes['value'];

                return $this->getPropertyAccessor()->getValue($obj, $property);

//            case self::METHOD_CALL:
//                $obj = $this->nodes['node']->evaluate($functions, $values);
//                if (!is_object($obj)) {
//                    throw new \RuntimeException('Unable to call method on a non-object.');
//                }
//
//                return call_user_func_array(array($obj, $this->nodes['attribute']->attributes['value']), $this->nodes['arguments']->evaluate($functions, $values));

            case self::ARRAY_CALL:
                $array = $this->nodes['node']->evaluate($functions, $values);
                if (!is_array($array) && !$array instanceof \ArrayAccess) {
                    throw new \RuntimeException('Unable to get an item on a non-array.');
                }

                return $array[$this->nodes['attribute']->evaluate($functions, $values)];

            case self::ALL_CALL:
                $obj = $this->nodes['node']->evaluate($functions, $values);
                if (!is_array($obj) && !$obj instanceof \Traversable) {
                    throw new \RuntimeException('Unable to iterate through a non-object.');
                }

                $name = $this->getNodeAttributeValue($this->nodes['node']);
                $result = true;
                foreach ($obj as $item) {
                    $evaluateResult = $this->nodes['arguments']
                        ->evaluate($functions, array_merge($values, [
                            Inflector::singularize($name) => $item
                        ]));
                    $evaluateResult = reset($evaluateResult);
                    if (!$evaluateResult) {
                        return false;
                    }
                    $result = $result && $evaluateResult;
                }

                return $result;

            case self::ANY_CALL:
                $obj = $this->nodes['node']->evaluate($functions, $values);
                if (!is_array($obj) && !$obj instanceof \Traversable) {
                    throw new \RuntimeException('Unable to iterate through a non-object.');
                }

                $name = $this->getNodeAttributeValue($this->nodes['node']);
                $result = false;
                foreach ($obj as $item) {
                    $evaluateResult = $this->nodes['arguments']
                        ->evaluate($functions, array_merge($values, [
                            Inflector::singularize($name) => $item
                        ]));
                    $evaluateResult = reset($evaluateResult);
                    if ($evaluateResult) {
                        return true;
                    }
                    $result = $result || $evaluateResult;
                }

                return $result;
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (static::$propertyAccessor === null) {
            static::$propertyAccessor = new PropertyAccessor();
        }
        return static::$propertyAccessor;
    }

    /**
     * @param Node $node
     * @return mixed
     */
    protected function getNodeAttributeValue(Node $node)
    {
        if ($node instanceof NameNode) {
            return $node->attributes['name'];
        } elseif ($node instanceof static) {
            return $node->nodes['attribute']->attributes['value'];
        }
    }
}
