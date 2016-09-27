<?php

namespace Oro\Component\ExpressionLanguage\Tests;

use Oro\Component\ExpressionLanguage\Lexer;
use Oro\Component\ExpressionLanguage\Node as CustomNode;
use Oro\Component\ExpressionLanguage\Parser;
use Symfony\Component\ExpressionLanguage\Node;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Symfony\Component\ExpressionLanguage\SyntaxError
     * @expectedExceptionMessage Array calls on a method call is not allowed around position 10.
     */
    public function testParseWithInvalidName()
    {
        $lexer = new Lexer();
        $parser = new Parser([]);
        $parser->parse($lexer->tokenize('foo.any()[3]'));
    }

    /**
     * @dataProvider getParseData
     *
     * @param Node\ $node
     * @param string $expression
     * @param array $names
     */
    public function testParse($node, $expression, $names = [])
    {
        $lexer = new Lexer();
        $parser = new Parser([]);
        $this->assertEquals($node, $parser->parse($lexer->tokenize($expression), $names));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function getParseData()
    {
        return [
            [
                new Node\NameNode('a'),
                'a',
                ['a'],
            ],
            [
                new Node\ConstantNode('a'),
                '"a"',
            ],
            [
                new Node\ConstantNode(3),
                '3',
            ],
            [
                new Node\ConstantNode(false),
                'false',
            ],
            [
                new Node\ConstantNode(true),
                'true',
            ],
            [
                new Node\ConstantNode(null),
                'null',
            ],
            [
                new Node\UnaryNode('-', new Node\ConstantNode(3)),
                '-3',
            ],
            [
                new CustomNode\BinaryNode('-', new Node\ConstantNode(3), new Node\ConstantNode(3)),
                '3 - 3',
            ],
            [
                new CustomNode\BinaryNode(
                    '*',
                    new CustomNode\BinaryNode('-', new Node\ConstantNode(3), new Node\ConstantNode(3)),
                    new Node\ConstantNode(2)
                ),
                '(3 - 3) * 2',
            ],
            [
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('bar'),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::PROPERTY_CALL
                ),
                'foo.bar',
                ['foo'],
            ],
            [
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('all'),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::ALL_CALL
                ),
                'foo.all()',
                ['foo'],
            ],
            [
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode('any'),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::ANY_CALL
                ),
                'foo.any()',
                ['foo'],
            ],
            [
                new CustomNode\GetAttrNode(
                    new Node\NameNode('foo'),
                    new Node\ConstantNode(3),
                    new Node\ArgumentsNode(),
                    CustomNode\GetAttrNode::ARRAY_CALL
                ),
                'foo[3]',
                ['foo'],
            ],
            [
                new Node\ConditionalNode(
                    new Node\ConstantNode(true),
                    new Node\ConstantNode(true),
                    new Node\ConstantNode(false)
                ),
                'true ? true : false',
            ],
            [
                new CustomNode\BinaryNode('matches', new Node\ConstantNode('foo'), new Node\ConstantNode('/foo/')),
                '"foo" matches "/foo/"',
            ],
            [
                $this->createGetAttrNode(
                    $this->createGetAttrNode(
                        new Node\NameNode('foo'),
                        'any',
                        CustomNode\GetAttrNode::ANY_CALL
                    ),
                    'all',
                    CustomNode\GetAttrNode::ALL_CALL
                ),
                'foo.any().all()',
                ['foo'],
            ],
            [
                new Node\NameNode('foo'),
                'bar',
                ['foo' => 'bar'],
            ],
        ];
    }

    public function testParseNestedCondition()
    {
        $arrayNode = new Node\ArrayNode();
        $arrayNode->addElement(new Node\ConstantNode('bar'));

        $left = new CustomNode\BinaryNode(
            'in',
            new CustomNode\GetAttrNode(
                new Node\NameNode('item'),
                new Node\ConstantNode('foo'),
                new Node\ArgumentsNode(),
                CustomNode\GetAttrNode::PROPERTY_CALL
            ),
            $arrayNode
        );

        $allsArguments = new Node\ArgumentsNode();
        $allsArguments->addElement(new CustomNode\BinaryNode(
            '>',
            new CustomNode\GetAttrNode(
                new Node\NameNode('item'),
                new Node\ConstantNode('index'),
                new Node\ArgumentsNode(),
                CustomNode\GetAttrNode::PROPERTY_CALL
            ),
            new Node\ConstantNode(10)
        ));

        $arguments = new Node\ArgumentsNode();
        $arguments->addElement(new CustomNode\BinaryNode(
            '>',
            new CustomNode\GetAttrNode(
                new Node\NameNode('value'),
                new Node\ConstantNode('index'),
                new Node\ArgumentsNode(),
                CustomNode\GetAttrNode::PROPERTY_CALL
            ),
            new Node\ConstantNode(10)
        ));

        $right = new CustomNode\GetAttrNode(
            new CustomNode\GetAttrNode(
                new Node\NameNode('item'),
                new Node\ConstantNode('values'),
                new Node\ArgumentsNode(),
                CustomNode\GetAttrNode::PROPERTY_CALL
            ),
            new Node\ConstantNode('all'),
            $arguments,
            CustomNode\GetAttrNode::ALL_CALL
        );

        $binaryArguments = new Node\ArgumentsNode();
        $binaryArguments->addElement(new CustomNode\BinaryNode(
            'and',
            $left,
            $right
        ));

        $node = new CustomNode\GetAttrNode(
            new Node\NameNode('items'),
            new Node\ConstantNode('any'),
            $binaryArguments,
            CustomNode\GetAttrNode::ANY_CALL
        );

        $expression = 'items.any(item.foo in ["bar"] and item.values.all(value.index > 10))';
        $names = ['items'];

        $lexer = new Lexer();
        $parser = new Parser([]);
        $this->assertEquals($node, $parser->parse($lexer->tokenize($expression), $names));
    }

    /**
     * @param Node\Node $node
     * @param $item
     * @param $type
     * @return CustomNode\GetAttrNode
     */
    private function createGetAttrNode(Node\Node $node, $item, $type)
    {
        return new CustomNode\GetAttrNode($node, new Node\ConstantNode($item), new Node\ArgumentsNode(), $type);
    }

    /**
     * @dataProvider getInvalidPostfixData
     * @expectedException \Symfony\Component\ExpressionLanguage\SyntaxError
     *
     * @param string $expr
     * @param array $names
     */
    public function testParseWithInvalidPostfixData($expr, array $names = [])
    {
        $lexer = new Lexer();
        $parser = new Parser([]);
        $parser->parse($lexer->tokenize($expr), $names);
    }

    /**
     * @return array
     */
    public function getInvalidPostfixData()
    {
        return [
            [
                'foo."#"',
                ['foo'],
            ],
            [
                'foo."bar"',
                ['foo'],
            ],
            [
                'foo.**',
                ['foo'],
            ],
            [
                'foo.123',
                ['foo'],
            ],
        ];
    }
}
