<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Expression\BinaryNode;
use Oro\Bundle\PricingBundle\Expression\ExpressionParser;
use Oro\Bundle\PricingBundle\Expression\NameNode;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\RelationNode;
use Oro\Bundle\PricingBundle\Expression\ValueNode;
use Oro\Bundle\PricingBundle\Provider\PriceRuleFieldsProvider;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceRuleRelationExpressions;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceRuleRelationExpressionsValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class PriceRuleRelationExpressionsValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testValidateSuccess()
    {
        $constraint = new PriceRuleRelationExpressions();
        $context = $this->getContextMock();

        $parser = $this->getMockBuilder(ExpressionParser::class)->disableOriginalConstructor()->getMock();
        $provider = $this->getMockBuilder(PriceRuleFieldsProvider::class)->disableOriginalConstructor()->getMock();
        $validator = new PriceRuleRelationExpressionsValidator($parser, $provider);

        $rule = new PriceRule();
        $ruleExpression = 'product.msrp.value';
        $currencyExpression = 'product.msrp.currency';
        $productUnitExpression = 'product.msrp.unit';
        $quantityExpression = 'product.msrp.quantity';
        $rule
            ->setRule($ruleExpression)
            ->setCurrencyExpression($currencyExpression)
            ->setProductUnitExpression($productUnitExpression)
            ->setQuantityExpression($quantityExpression);

        $provider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'Oro\Bundle\ProductBundle\Entity\Product',
                            'msrp',
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice'
                        ],
                        [
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                            'unit',
                            'Oro\Bundle\ProductBundle\Entity\ProductUnit'
                        ],
                    ]
                )
            );
        $parser->method('parse')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            $currencyExpression,
                            new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'currency')
                        ],
                        [
                            $productUnitExpression,
                            new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'unit')
                        ],
                        [
                            $quantityExpression,
                            new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'quantity')
                        ],
                        [
                            $ruleExpression,
                            new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'value')
                        ],
                    ]
                )
            );

        $context->expects($this->never())->method('buildViolation');

        $validator->initialize($context);
        $validator->validate($rule, $constraint);
    }

    /**
     * @dataProvider validateCurrencyFailDataProvider
     *
     * @param string $currencyExpression
     * @param string $ruleExpression
     * @param NodeInterface $parsedCurrencyExpression
     * @param NodeInterface $ruleNode
     * @param string $message
     * @param array $messageParams
     */
    public function testValidateCurrencyFail(
        $currencyExpression,
        $ruleExpression,
        $message,
        array $messageParams,
        NodeInterface $parsedCurrencyExpression,
        NodeInterface $ruleNode = null
    ) {
        $constraint = new PriceRuleRelationExpressions();
        $context = $this->getContextMock();

        $parser = $this->getMockBuilder(ExpressionParser::class)->disableOriginalConstructor()->getMock();
        $provider = $this->getMockBuilder(PriceRuleFieldsProvider::class)->disableOriginalConstructor()->getMock();
        $provider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'Oro\Bundle\ProductBundle\Entity\Product',
                            'msrp',
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice'
                        ],
                        [
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                            'currency',
                            null
                        ],
                    ]
                )
            );

        $validator = new PriceRuleRelationExpressionsValidator($parser, $provider);

        $rule = new PriceRule();
        $rule->setCurrencyExpression($currencyExpression)
            ->setRule($ruleExpression);

        $parser->expects($this->at(0))->method('parse')->with($currencyExpression)->willReturn($parsedCurrencyExpression);
        if ($ruleNode) {
            $parser->expects($this->at(1))->method('parse')->with($ruleExpression)->willReturn($ruleNode);
        }

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('currencyExpression')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($message, $messageParams)
            ->willReturn($builder);

        $validator->initialize($context);
        $validator->validate($rule, $constraint);
    }

    /**
     * @return array
     */
    public function validateCurrencyFailDataProvider()
    {
        return [
            'test_relation_field_is_not_currency' =>
                [
                    'currencyExpression' => 'product.msrp.quantity',
                    'ruleExpression' => 'product.msrp.value',
                    'message' => PriceRuleRelationExpressionsValidator::FIELD_ARE_NOT_ALLOWED_MESSAGE,
                    'messageParams' => ['%fieldName%' => 'quantity'],
                    'parsedCurrencyExpression' => new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'quantity'),
                    'ruleNode' => new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'value'),
                ],
            'test_to_many_nodes' =>
                [
                    'currencyExpression' => 'product.msrp.currency + 10',
                    'ruleExpression' => 'product.msrp.value',
                    'message' => PriceRuleRelationExpressionsValidator::ONE_EXPRESSION_ALLOWED_MESSAGE,
                    'messageParams' => [],
                    'parsedCurrencyExpression' => new BinaryNode(
                        new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'quantity'),
                        new ValueNode(10),
                        '+'
                    ),
                ],
            'test_is_relation_node' =>
                [
                    'currencyExpression' => 'product.id',
                    'ruleExpression' => 'product.msrp.value',
                    'message' => PriceRuleRelationExpressionsValidator::ONLY_PRICE_RELATION_MESSAGE,
                    'messageParams' => [],
                    'parsedCurrencyExpression' => new NameNode('Oro\Bundle\ProductBundle\Entity\Product', 'id'),
                ],
            'test_relation_exists_in_rule_condition' =>
                [
                    'currencyExpression' => 'product.msrp.currency',
                    'ruleExpression' => 'product.map.value',
                    'message' => PriceRuleRelationExpressionsValidator::RELATION_NOT_IN_RULE_MESSAGE,
                    'messageParams' => ['%relationName%' => 'msrp'],
                    'parsedCurrencyExpression' => new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'currency'),
                    'ruleNode' => new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'map', 'value'),
                ],
        ];
    }
    /**
     * @dataProvider validateQuantityFailDataProvider
     *
     * @param string $quantityExpression
     * @param string $ruleExpression
     * @param NodeInterface $parsedQuantityExpression
     * @param NodeInterface $ruleNode
     * @param string $message
     * @param array $messageParams
     */
    public function testValidateQuantityFail(
        $quantityExpression,
        $ruleExpression,
        $message,
        array $messageParams,
        NodeInterface $parsedQuantityExpression,
        NodeInterface $ruleNode = null
    ) {
        $constraint = new PriceRuleRelationExpressions();
        $context = $this->getContextMock();

        $parser = $this->getMockBuilder(ExpressionParser::class)->disableOriginalConstructor()->getMock();
        $provider = $this->getMockBuilder(PriceRuleFieldsProvider::class)->disableOriginalConstructor()->getMock();
        $validator = new PriceRuleRelationExpressionsValidator($parser, $provider);

        $rule = new PriceRule();
        $rule->setQuantityExpression($quantityExpression)
            ->setRule($ruleExpression);

        $parser->expects($this->at(0))->method('parse')->with($quantityExpression)->willReturn($parsedQuantityExpression);
        if ($ruleNode) {
            $parser->expects($this->at(1))->method('parse')->with($ruleExpression)->willReturn($ruleNode);
        }

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('quantityExpression')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($message, $messageParams)
            ->willReturn($builder);

        $validator->initialize($context);
        $validator->validate($rule, $constraint);
    }

    /**
     * @return array
     */
    public function validateQuantityFailDataProvider()
    {
        return [
            'test_more_then_one_relation_node' =>
                [
                    'quantityExpression' => 'product.msrp.quantity + product.map.quantity',
                    'ruleExpression' => 'product.msrp.value',
                    'message' => PriceRuleRelationExpressionsValidator::TOO_MANY_RELATIONS_MESSAGE,
                    'messageParams' => [],
                    'parsedQuantityExpression' => new BinaryNode(
                        new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'quantity'),
                        new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'map', 'quantity'),
                        '+'
                    ),
                ],
            'test_name_node' =>
                [
                    'quantityExpression' => 'product.id + 10',
                    'ruleExpression' => 'product.msrp.value',
                    'message' => PriceRuleRelationExpressionsValidator::TOO_MANY_RELATIONS_MESSAGE,
                    'messageParams' => [],
                    'parsedQuantityExpression' => new BinaryNode(
                        new NameNode('Oro\Bundle\ProductBundle\Entity\Product', 'id'),
                        new ValueNode(10),
                        '+'
                    ),
                ],
            'test_relation_exists_in_rule_condition' =>
                [
                    'quantityExpression' => 'product.msrp.quantity',
                    'ruleExpression' => 'product.map.value',
                    'message' => PriceRuleRelationExpressionsValidator::RELATION_NOT_IN_RULE_MESSAGE,
                    'messageParams' => ['%relationName%' => 'msrp'],
                    'parsedQuantityExpression' => new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'quantity'),
                    'ruleNode' => new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'map', 'value'),
                ],
        ];
    }

    /**
     * @dataProvider validateProductUnitFailDataProvider
     *
     * @param string $productUnitExpression
     * @param string $ruleExpression
     * @param NodeInterface $parsedUnitExpression
     * @param NodeInterface $ruleNode
     * @param string $message
     * @param array $messageParams
     */
    public function testValidateProductUnitFail(
        $productUnitExpression,
        $ruleExpression,
        $message,
        array $messageParams,
        NodeInterface $parsedUnitExpression,
        NodeInterface $ruleNode = null
    ) {
        $constraint = new PriceRuleRelationExpressions();
        $context = $this->getContextMock();

        $parser = $this->getMockBuilder(ExpressionParser::class)->disableOriginalConstructor()->getMock();
        $provider = $this->getMockBuilder(PriceRuleFieldsProvider::class)->disableOriginalConstructor()->getMock();
        $provider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'Oro\Bundle\ProductBundle\Entity\Product',
                            'msrp',
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice'
                        ],
                        [
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                            'currency',
                            null
                        ],
                    ]
                )
            );
        $validator = new PriceRuleRelationExpressionsValidator($parser, $provider);

        $rule = new PriceRule();
        $rule->setProductUnitExpression($productUnitExpression)
            ->setRule($ruleExpression);

        $parser->expects($this->at(0))->method('parse')->with($productUnitExpression)->willReturn($parsedUnitExpression);
        if ($ruleNode) {
            $parser->expects($this->at(1))->method('parse')->with($ruleExpression)->willReturn($ruleNode);
        }

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('productUnitExpression')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($message, $messageParams)
            ->willReturn($builder);

        $validator->initialize($context);
        $validator->validate($rule, $constraint);
    }

    /**
     * @return array
     */
    public function validateProductUnitFailDataProvider()
    {
        return [
            'test_relation_not_product_unit_holder' =>
                [
                    'productUnitExpression' => 'product.msrp.quantity',
                    'ruleExpression' => 'product.msrp.value',
                    'message' => PriceRuleRelationExpressionsValidator::FIELD_ARE_NOT_ALLOWED_MESSAGE,
                    'messageParams' => ['%fieldName%' => 'quantity'],
                    'parsedUnitExpression' => new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'quantity'),
                    'ruleNode' => new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'value'),
                ],
            'test_to_many_nodes' =>
                [
                    'productUnitExpression' => 'product.msrp.unit + 10',
                    'ruleExpression' => 'product.msrp.value',
                    'message' => PriceRuleRelationExpressionsValidator::ONE_EXPRESSION_ALLOWED_MESSAGE,
                    'messageParams' => [],
                    'parsedUnitExpression' => new BinaryNode(
                        new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'unit'),
                        new ValueNode(10),
                        '+'
                    ),
                ],
            'test_is_relation_node' =>
                [
                    'productUnitExpression' => 'product.msrp.id',
                    'ruleExpression' => 'product.msrp.value',
                    'message' => PriceRuleRelationExpressionsValidator::ONLY_PRICE_RELATION_MESSAGE,
                    'messageParams' => [],
                    'parsedUnitExpression' => new NameNode('Oro\Bundle\ProductBundle\Entity\Product', 'id'),
                ],
            'test_relation_exists_in_rule_condition' =>
                [
                    'productUnitExpression' => 'product.msrp.unit',
                    'ruleExpression' => 'product.map.value',
                    'message' => PriceRuleRelationExpressionsValidator::RELATION_NOT_IN_RULE_MESSAGE,
                    'messageParams' => ['%relationName%' => 'msrp'],
                    'parsedUnitExpression' => new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'unit'),
                    'ruleNode' => new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'map', 'value'),
                ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface $context
     */
    protected function getContextMock()
    {
        return $this->getMock(ExecutionContextInterface::class);
    }
}
