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

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceRuleRelationExpressionsValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceRuleRelationExpressionsValidator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

    /**
     * @var ExpressionParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parser;

    /**
     * @var PriceRuleFieldsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    protected function setUp()
    {
        $this->context = $this->getMock(ExecutionContextInterface::class);

        $this->parser = $this->getMockBuilder(ExpressionParser::class)->disableOriginalConstructor()->getMock();
        $this->fieldProvider = $this->getMockBuilder(PriceRuleFieldsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator = new PriceRuleRelationExpressionsValidator($this->parser, $this->fieldProvider);
        $this->validator->initialize($this->context);
    }

    public function testValidateSuccess()
    {
        $rule = new PriceRule();
        $rule
            ->setRule('product.msrp.value')
            ->setCurrencyExpression('product.msrp.currency')
            ->setProductUnitExpression('product.msrp.unit')
            ->setQuantityExpression('product.msrp.quantity');

        $this->fieldProvider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'Oro\Bundle\ProductBundle\Entity\Product',
                            'msrp',
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                        ],
                        [
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                            'unit',
                            'Oro\Bundle\ProductBundle\Entity\ProductUnit',
                        ],
                    ]
                )
            );
        $this->parser->method('parse')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            $rule->getCurrencyExpression(),
                            new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'currency'),
                        ],
                        [
                            $rule->getProductUnitExpression(),
                            new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'unit'),
                        ],
                        [
                            $rule->getQuantityExpression(),
                            new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'quantity'),
                        ],
                        [
                            $rule->getRule(),
                            new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'value'),
                        ],
                    ]
                )
            );

        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($rule, new PriceRuleRelationExpressions());
    }

    /**
     * @dataProvider validateCurrencyFailDataProvider
     *
     * @param string $currencyExpression
     * @param NodeInterface $parsedCurrencyExpression
     * @param string $message
     * @param array $messageParams
     */
    public function testValidateCurrencyFail(
        $currencyExpression,
        $message,
        array $messageParams,
        NodeInterface $parsedCurrencyExpression
    ) {
        $this->fieldProvider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'Oro\Bundle\ProductBundle\Entity\Product',
                            'msrp',
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                        ],
                        [
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                            'currency',
                            null,
                        ],
                    ]
                )
            );


        $rule = new PriceRule();
        $rule->setCurrencyExpression($currencyExpression);

        $this->parser->expects($this->at(0))
            ->method('parse')
            ->with($currencyExpression)
            ->willReturn($parsedCurrencyExpression);

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('currencyExpression')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message, $messageParams)
            ->willReturn($builder);

        $this->validator->validate($rule, new PriceRuleRelationExpressions());
    }

    /**
     * @return array
     */
    public function validateCurrencyFailDataProvider()
    {
        return [
            'test_to_many_nodes' => [
                'currencyExpression' => 'product.msrp.currency + 10',
                'message' => PriceRuleRelationExpressionsValidator::ONE_EXPRESSION_ALLOWED_MESSAGE,
                'messageParams' => [],
                'parsedCurrencyExpression' => new BinaryNode(
                    new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'quantity'),
                    new ValueNode(10),
                    '+'
                ),
            ],
            'test_is_relation_node' => [
                'currencyExpression' => 'product.id',
                'message' => PriceRuleRelationExpressionsValidator::ONLY_PRICE_RELATION_MESSAGE,
                'messageParams' => [],
                'parsedCurrencyExpression' => new NameNode('Oro\Bundle\ProductBundle\Entity\Product', 'id'),
            ],
        ];
    }

    /**
     * @dataProvider validateCurrencyWithRuleExpressionFailDataProvider
     *
     * @param string $currencyExpression
     * @param string $ruleExpression
     * @param NodeInterface $parsedCurrencyExpression
     * @param NodeInterface $parsedRuleExpression
     * @param string $message
     * @param array $messageParams
     */
    public function testValidateCurrencyWithRuleExpressionFail(
        $currencyExpression,
        $ruleExpression,
        $message,
        array $messageParams,
        NodeInterface $parsedCurrencyExpression,
        NodeInterface $parsedRuleExpression = null
    ) {
        $this->fieldProvider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'Oro\Bundle\ProductBundle\Entity\Product',
                            'msrp',
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                        ],
                        [
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                            'currency',
                            null,
                        ],
                    ]
                )
            );


        $rule = new PriceRule();
        $rule->setCurrencyExpression($currencyExpression)
            ->setRule($ruleExpression);

        $this->parser->expects($this->at(0))
            ->method('parse')
            ->with($currencyExpression)
            ->willReturn($parsedCurrencyExpression);
        $this->parser->expects($this->at(1))->method('parse')->with($ruleExpression)->willReturn($parsedRuleExpression);

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('currencyExpression')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message, $messageParams)
            ->willReturn($builder);

        $this->validator->validate($rule, new PriceRuleRelationExpressions());
    }

    /**
     * @return array
     */
    public function validateCurrencyWithRuleExpressionFailDataProvider()
    {
        return [
            'test_relation_field_is_not_currency' => [
                'currencyExpression' => 'product.msrp.quantity',
                'ruleExpression' => 'product.msrp.value',
                'message' => PriceRuleRelationExpressionsValidator::FIELD_ARE_NOT_ALLOWED_MESSAGE,
                'messageParams' => ['%fieldName%' => 'quantity'],
                'parsedCurrencyExpression' => new RelationNode(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'msrp',
                    'quantity'
                ),
                'parsedRuleExpression' => new RelationNode(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'msrp',
                    'value'
                ),
            ],
            'test_relation_exists_in_rule_condition' => [
                'currencyExpression' => 'product.msrp.currency',
                'ruleExpression' => 'product.map.value',
                'message' => PriceRuleRelationExpressionsValidator::RELATION_NOT_IN_RULE_MESSAGE,
                'messageParams' => ['%relationName%' => 'msrp'],
                'parsedCurrencyExpression' => new RelationNode(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'msrp',
                    'currency'
                ),
                'parsedRuleExpression' => new RelationNode(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'map',
                    'value'
                ),
            ],
        ];
    }

    /**
     * @dataProvider validateQuantityFailDataProvider
     *
     * @param string $quantityExpression
     * @param NodeInterface $parsedQuantityExpression
     * @param string $message
     * @param array $messageParams
     */
    public function testValidateQuantityFail(
        $quantityExpression,
        $message,
        array $messageParams,
        NodeInterface $parsedQuantityExpression
    ) {
        $rule = new PriceRule();
        $rule->setQuantityExpression($quantityExpression);

        $this->parser->expects($this->at(0))->method('parse')->with($quantityExpression)->willReturn(
            $parsedQuantityExpression
        );

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('quantityExpression')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message, $messageParams)
            ->willReturn($builder);

        $this->validator->validate($rule, new PriceRuleRelationExpressions());
    }

    /**
     * @return array
     */
    public function validateQuantityFailDataProvider()
    {
        return [
            'test_more_then_one_relation_node' => [
                'quantityExpression' => 'product.msrp.quantity + product.map.quantity',
                'message' => PriceRuleRelationExpressionsValidator::TOO_MANY_RELATIONS_MESSAGE,
                'messageParams' => [],
                'parsedQuantityExpression' => new BinaryNode(
                    new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'quantity'),
                    new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'map', 'quantity'),
                    '+'
                ),
            ],
            'test_name_node' => [
                'quantityExpression' => 'product.id + 10',
                'message' => PriceRuleRelationExpressionsValidator::TOO_MANY_RELATIONS_MESSAGE,
                'messageParams' => [],
                'parsedQuantityExpression' => new BinaryNode(
                    new NameNode('Oro\Bundle\ProductBundle\Entity\Product', 'id'),
                    new ValueNode(10),
                    '+'
                ),
            ],
        ];
    }

    /**
     * @dataProvider validateQuantityWithRuleExpressionFailDataProvider
     *
     * @param string $quantityExpression
     * @param string $ruleExpression
     * @param NodeInterface $parsedQuantityExpression
     * @param NodeInterface $parsedRuleExpression
     * @param string $message
     * @param array $messageParams
     */
    public function testValidateQuantityWithRuleExpressionFail(
        $quantityExpression,
        $ruleExpression,
        $message,
        array $messageParams,
        NodeInterface $parsedQuantityExpression,
        NodeInterface $parsedRuleExpression = null
    ) {
        $rule = new PriceRule();
        $rule->setQuantityExpression($quantityExpression)
            ->setRule($ruleExpression);

        $this->parser->expects($this->at(0))
            ->method('parse')
            ->with($quantityExpression)
            ->willReturn($parsedQuantityExpression);
        $this->parser->expects($this->at(1))->method('parse')->with($ruleExpression)->willReturn($parsedRuleExpression);

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('quantityExpression')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message, $messageParams)
            ->willReturn($builder);

        $this->validator->validate($rule, new PriceRuleRelationExpressions());
    }

    /**
     * @return array
     */
    public function validateQuantityWithRuleExpressionFailDataProvider()
    {
        return [
            'test_relation_exists_in_rule_condition' => [
                'quantityExpression' => 'product.msrp.quantity',
                'ruleExpression' => 'product.map.value',
                'message' => PriceRuleRelationExpressionsValidator::RELATION_NOT_IN_RULE_MESSAGE,
                'messageParams' => ['%relationName%' => 'msrp'],
                'parsedQuantityExpression' => new RelationNode(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'msrp',
                    'quantity'
                ),
                'parsedRuleExpression' => new RelationNode(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'map',
                    'value'
                ),
            ],
        ];
    }

    /**
     * @dataProvider validateProductUnitFailDataProvider
     *
     * @param string $productUnitExpression
     * @param NodeInterface $parsedUnitExpression
     * @param string $message
     * @param array $messageParams
     */
    public function testValidateProductUnitFail(
        $productUnitExpression,
        $message,
        array $messageParams,
        NodeInterface $parsedUnitExpression
    ) {
        $this->fieldProvider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'Oro\Bundle\ProductBundle\Entity\Product',
                            'msrp',
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                        ],
                        [
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                            'currency',
                            null,
                        ],
                    ]
                )
            );
        $rule = new PriceRule();
        $rule->setProductUnitExpression($productUnitExpression);

        $this->parser->expects($this->at(0))
            ->method('parse')
            ->with($productUnitExpression)
            ->willReturn($parsedUnitExpression);

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('productUnitExpression')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message, $messageParams)
            ->willReturn($builder);

        $this->validator->validate($rule, new PriceRuleRelationExpressions());
    }

    /**
     * @return array
     */
    public function validateProductUnitFailDataProvider()
    {
        return [
            'test_to_many_nodes' => [
                'productUnitExpression' => 'product.msrp.unit + 10',
                'message' => PriceRuleRelationExpressionsValidator::ONE_EXPRESSION_ALLOWED_MESSAGE,
                'messageParams' => [],
                'parsedUnitExpression' => new BinaryNode(
                    new RelationNode('Oro\Bundle\ProductBundle\Entity\Product', 'msrp', 'unit'),
                    new ValueNode(10),
                    '+'
                ),
            ],
            'test_is_relation_node' => [
                'productUnitExpression' => 'product.msrp.id',
                'message' => PriceRuleRelationExpressionsValidator::ONLY_PRICE_RELATION_MESSAGE,
                'messageParams' => [],
                'parsedUnitExpression' => new NameNode('Oro\Bundle\ProductBundle\Entity\Product', 'id'),
            ],
        ];
    }

    /**
     * @dataProvider validateProductUnitWithRuleExpressionFailDataProvider
     *
     * @param string $productUnitExpression
     * @param string $ruleExpression
     * @param NodeInterface $parsedUnitExpression
     * @param NodeInterface $parsedRuleExpression
     * @param string $message
     * @param array $messageParams
     */
    public function testValidateProductUnitFailWithRuleExpression(
        $productUnitExpression,
        $ruleExpression,
        $message,
        array $messageParams,
        NodeInterface $parsedUnitExpression,
        NodeInterface $parsedRuleExpression = null
    ) {
        $this->fieldProvider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            'Oro\Bundle\ProductBundle\Entity\Product',
                            'msrp',
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                        ],
                        [
                            'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice',
                            'currency',
                            null,
                        ],
                    ]
                )
            );
        $rule = new PriceRule();
        $rule->setProductUnitExpression($productUnitExpression)
            ->setRule($ruleExpression);

        $this->parser->expects($this->at(0))->method('parse')
            ->with($productUnitExpression)
            ->willReturn($parsedUnitExpression);
        $this->parser->expects($this->at(1))->method('parse')
            ->with($ruleExpression)
            ->willReturn($parsedRuleExpression);

        $builder = $this->getMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->at(0))->method('atPath')->with('productUnitExpression')->willReturn($builder);
        $builder->expects($this->at(1))->method('addViolation');
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($message, $messageParams)
            ->willReturn($builder);

        $this->validator->validate($rule, new PriceRuleRelationExpressions());
    }

    /**
     * @return array
     */
    public function validateProductUnitWithRuleExpressionFailDataProvider()
    {
        return [
            'test_relation_not_product_unit_holder' => [
                'productUnitExpression' => 'product.msrp.quantity',
                'ruleExpression' => 'product.msrp.value',
                'message' => PriceRuleRelationExpressionsValidator::FIELD_ARE_NOT_ALLOWED_MESSAGE,
                'messageParams' => ['%fieldName%' => 'quantity'],
                'parsedUnitExpression' => new RelationNode(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'msrp',
                    'quantity'
                ),
                'parsedRuleExpression' => new RelationNode(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'msrp',
                    'value'
                ),
            ],
            'test_relation_exists_in_rule_condition' => [
                'productUnitExpression' => 'product.msrp.unit',
                'ruleExpression' => 'product.map.value',
                'message' => PriceRuleRelationExpressionsValidator::RELATION_NOT_IN_RULE_MESSAGE,
                'messageParams' => ['%relationName%' => 'msrp'],
                'parsedUnitExpression' => new RelationNode(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'msrp',
                    'unit'
                ),
                'parsedRuleExpression' => new RelationNode(
                    'Oro\Bundle\ProductBundle\Entity\Product',
                    'map',
                    'value'
                ),
            ],
        ];
    }
}
