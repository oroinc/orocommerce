<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceRuleRelationExpressions;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceRuleRelationExpressionsValidator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\FieldsProviderInterface;
use Oro\Component\Expression\Node\BinaryNode;
use Oro\Component\Expression\Node\NameNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\RelationNode;
use Oro\Component\Expression\Node\ValueNode;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceRuleRelationExpressionsValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PriceRuleRelationExpressionsValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $validator;

    /**
     * @var ExpressionParser|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $parser;

    /**
     * @var FieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldsProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var string
     */
    protected $translatedLabel = ' key was translated.';

    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->parser = $this->getMockBuilder(ExpressionParser::class)->disableOriginalConstructor()->getMock();
        $this->fieldsProvider = $this->createMock(FieldsProviderInterface::class);
        $this->fieldsProvider->method('getDetailedFieldsInformation')
            ->willReturn(
                [
                    'msrp' => [
                        'name' => 'msrp',
                        'type' => 'manyToOne',
                        'label' => 'MSRP',
                        'relation_type' => 'manyToOne',
                        'related_entity_name' => 'Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice'
                    ]
                ]
            );
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->translator->expects($this->any())
            ->method('trans')
            ->with(
                $this->logicalOr(
                    $this->equalTo('oro.pricing.pricerule.product_unit.label'),
                    $this->equalTo('oro.pricing.pricerule.quantity.label'),
                    $this->equalTo('oro.pricing.pricerule.currency.label')
                )
            )
            ->will(
                $this->returnCallback(
                    function ($param) {
                        return $param . $this->translatedLabel;
                    }
                )
            );

        $this->validator = new PriceRuleRelationExpressionsValidator(
            $this->parser,
            $this->fieldsProvider,
            $this->translator
        );
        $this->validator->initialize($this->context);
    }

    /**
     * @dataProvider validaDataProvider
     *
     * @param string $ruleExpression
     * @param NodeInterface $ruleNode
     * @param string $currencyExpression
     * @param NodeInterface $currencyNode
     * @param string $unitExpression
     * @param NodeInterface $unitNode
     * @param string $qtyExpression
     * @param NodeInterface $qtyNode
     */
    public function testValidateSuccess(
        $ruleExpression,
        NodeInterface $ruleNode,
        $currencyExpression,
        NodeInterface $currencyNode,
        $unitExpression,
        NodeInterface $unitNode,
        $qtyExpression,
        NodeInterface $qtyNode
    ) {
        $rule = new PriceRule();
        $rule->setRule($ruleExpression)
            ->setCurrencyExpression($currencyExpression)
            ->setProductUnitExpression($unitExpression)
            ->setQuantityExpression($qtyExpression);

        $this->fieldsProvider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [Product::class, 'msrp', PriceAttributeProductPrice::class],
                        [PriceAttributeProductPrice::class, 'unit', ProductUnit::class],
                        [Product::class, 'primaryUnit', ProductUnitPrecision::class],
                        [ProductUnitPrecision::class, 'unit', ProductUnit::class],
                    ]
                )
            );
        $this->fieldsProvider->method('isRelation')
            ->will(
                $this->returnValueMap(
                    [
                        [Product::class, 'msrp', true],
                        [Product::class, 'primaryUnit', true],
                        [PriceAttributeProductPrice::class, 'unit', true],
                        [ProductUnitPrecision::class, 'unit', true],
                    ]
                )
            );
        $this->parser->method('parse')
            ->will(
                $this->returnValueMap(
                    [
                        [$rule->getCurrencyExpression(), $currencyNode],
                        [$rule->getProductUnitExpression(), $unitNode],
                        [$rule->getQuantityExpression(), $qtyNode],
                        [$rule->getRule(), $ruleNode]
                    ]
                )
            );

        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validate($rule, new PriceRuleRelationExpressions());
    }

    /**
     * @return array
     */
    public function validaDataProvider()
    {
        return [
            'valid expression with base price relation' => [
                'rule' => 'product.msrp.value',
                'ruleNode' => new RelationNode(Product::class, 'msrp', 'value'),
                'currencyExpression' => 'product.msrp.currency',
                'currencyNode' => new RelationNode(Product::class, 'msrp', 'currency'),
                'unitExpression' => 'product.msrp.unit',
                'unitNode' => new RelationNode(Product::class, 'msrp', 'unit'),
                'qtyExpression' => 'product.msrp.quantity',
                'qtyNode' => new RelationNode(Product::class, 'msrp', 'quantity')
            ],
            'valid expression with base price relation and qty sum' => [
                'rule' => 'product.msrp.value',
                'ruleNode' => new RelationNode(Product::class, 'msrp', 'value'),
                'currencyExpression' => 'product.msrp.currency',
                'currencyNode' => new RelationNode(Product::class, 'msrp', 'currency'),
                'unitExpression' => 'product.msrp.unit',
                'unitNode' => new RelationNode(Product::class, 'msrp', 'unit'),
                'qtyExpression' => 'product.msrp.quantity + 10',
                'qtyNode' => new BinaryNode(
                    new RelationNode(Product::class, 'msrp', 'quantity'),
                    new ValueNode(10),
                    '+'
                )
            ],
            'valid expression with non base price relation' => [
                'rule' => 'product.msrp.value',
                'ruleNode' => new RelationNode(Product::class, 'msrp', 'value'),
                'currencyExpression' => 'product.msrp.currency',
                'currencyNode' => new RelationNode(Product::class, 'msrp', 'currency'),
                'unitExpression' => 'product.primaryUnit.unit',
                'unitNode' => new RelationNode(Product::class, 'msrp', 'unit'),
                'qtyExpression' => 'product.msrp.quantity',
                'qtyNode' => new RelationNode(Product::class, 'msrp', 'quantity')
            ],
        ];
    }

    public function testValidateWithSyntaxError()
    {
        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method($this->anything());
        $this->validator->initialize($context);

        $rule = new PriceRule();
        $rule->setRule('product.msrp.value')
            ->setProductUnitExpression('pricelist[1].');

        $this->parser->expects($this->any())
            ->method('parse')
            ->willThrowException(new SyntaxError('Expected name around position 14.'));

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
        $this->fieldsProvider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [Product::class, 'msrp', PriceAttributeProductPrice::class],
                        [PriceAttributeProductPrice::class, 'currency', null]
                    ]
                )
            );

        $rule = new PriceRule();
        $rule->setCurrencyExpression($currencyExpression);

        $this->parser->expects($this->at(0))
            ->method('parse')
            ->with($currencyExpression)
            ->willReturn($parsedCurrencyExpression);

        $this->parser->expects($this->at(1))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $this->parser->expects($this->at(2))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
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
                'message' => 'oro.pricing.validators.one_expression_allowed.message',
                'messageParams' => ['%fieldName%' => 'oro.pricing.pricerule.currency.label' . $this->translatedLabel],
                'parsedCurrencyExpression' => new BinaryNode(
                    new RelationNode(Product::class, 'msrp', 'quantity'),
                    new ValueNode(10),
                    '+'
                ),
            ],
            'test_is_relation_node' => [
                'currencyExpression' => 'product.id',
                'message' => 'oro.pricing.validators.only_price_relations_available.message',
                'messageParams' => ['%fieldName%' => 'oro.pricing.pricerule.currency.label' . $this->translatedLabel],
                'parsedCurrencyExpression' => new NameNode(Product::class, 'id'),
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
        $this->fieldsProvider->method('getRealClassName')
            ->will(
                $this->returnValueMap(
                    [
                        [Product::class, 'msrp', PriceAttributeProductPrice::class],
                        [PriceAttributeProductPrice::class, 'currency', null]
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

        $this->parser->expects($this->at(1))
            ->method('parse')
            ->with($ruleExpression)
            ->willReturn($parsedRuleExpression);

        $this->parser->expects($this->at(2))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $this->parser->expects($this->at(3))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
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
                'message' => 'oro.pricing.validators.field_is_not_allowed_as.message',
                'messageParams' => [
                    '%fieldName%' => 'quantity',
                    '%inputName%' => 'oro.pricing.pricerule.currency.label' . $this->translatedLabel
                ],
                'parsedCurrencyExpression' => new RelationNode(Product::class, 'msrp', 'quantity'),
                'parsedRuleExpression' => new RelationNode(Product::class, 'msrp', 'value'),
            ],
            'test_relation_exists_in_rule_condition' => [
                'currencyExpression' => 'product.msrp.currency',
                'ruleExpression' => 'product.map.value',
                'message' => 'oro.pricing.validators.relation_not_in_rule.message',
                'messageParams' => [
                    '%relationName%' => 'msrp',
                    '%fieldName%' => 'oro.pricing.pricerule.currency.label' . $this->translatedLabel
                ],
                'parsedCurrencyExpression' => new RelationNode(Product::class, 'msrp', 'currency'),
                'parsedRuleExpression' => new RelationNode(Product::class, 'map', 'value'),
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

        $this->parser->expects($this->at(0))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $this->parser->expects($this->at(1))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $this->parser->expects($this->at(2))
            ->method('parse')
            ->with($quantityExpression)
            ->willReturn($parsedQuantityExpression);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
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
                'message' => 'oro.pricing.validators.too_many_relations.message',
                'messageParams' => ['%fieldName%' => 'oro.pricing.pricerule.quantity.label' . $this->translatedLabel],
                'parsedQuantityExpression' => new BinaryNode(
                    new RelationNode(Product::class, 'msrp', 'quantity'),
                    new RelationNode(Product::class, 'map', 'quantity'),
                    '+'
                ),
            ],
            'test_name_node' => [
                'quantityExpression' => 'product.id + 10',
                'message' => 'oro.pricing.validators.too_many_relations.message',
                'messageParams' => ['%fieldName%' => 'oro.pricing.pricerule.quantity.label' . $this->translatedLabel],
                'parsedQuantityExpression' => new BinaryNode(
                    new NameNode(Product::class, 'id'),
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
        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->with(Product::class, 'msrp')
            ->willReturn(PriceAttributeProductPrice::class);

        $rule = new PriceRule();
        $rule->setQuantityExpression($quantityExpression)
            ->setRule($ruleExpression);

        $this->parser->expects($this->at(0))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $this->parser->expects($this->at(1))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $this->parser->expects($this->at(2))
            ->method('parse')
            ->with($quantityExpression)
            ->willReturn($parsedQuantityExpression);

        $this->parser->expects($this->at(3))
            ->method('parse')
            ->with($ruleExpression)
            ->willReturn($parsedRuleExpression);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
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
                'message' => 'oro.pricing.validators.relation_not_in_rule.message',
                'messageParams' => [
                    '%relationName%' => 'msrp',
                    '%fieldName%' => 'oro.pricing.pricerule.quantity.label' . $this->translatedLabel
                ],
                'parsedQuantityExpression' => new RelationNode(Product::class, 'msrp', 'quantity'),
                'parsedRuleExpression' => new RelationNode(Product::class, 'map', 'value'),
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
        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->with(Product::class, 'msrp')
            ->willReturn(PriceAttributeProductPrice::class);

        $rule = new PriceRule();
        $rule->setProductUnitExpression($productUnitExpression);

        $this->parser->expects($this->at(0))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $this->parser->expects($this->at(1))
            ->method('parse')
            ->with($productUnitExpression)
            ->willReturn($parsedUnitExpression);

        $this->parser->expects($this->at(2))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
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
                'message' => 'oro.pricing.validators.one_expression_allowed.message',
                'messageParams' => [
                    '%fieldName%' => 'oro.pricing.pricerule.product_unit.label' . $this->translatedLabel
                ],
                'parsedUnitExpression' => new BinaryNode(
                    new RelationNode(Product::class, 'msrp', 'unit'),
                    new ValueNode(10),
                    '+'
                ),
            ],
            'test_is_relation_node' => [
                'productUnitExpression' => 'product.msrp.id',
                'message' => 'oro.pricing.validators.only_price_relations_available.message',
                'messageParams' => [
                    '%fieldName%' => 'oro.pricing.pricerule.product_unit.label' . $this->translatedLabel
                ],
                'parsedUnitExpression' => new NameNode(Product::class, 'id'),
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
        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->with(Product::class, 'msrp')
            ->willReturn(PriceAttributeProductPrice::class);

        $rule = new PriceRule();
        $rule->setProductUnitExpression($productUnitExpression)
            ->setRule($ruleExpression);

        $this->parser->expects($this->at(0))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $this->parser->expects($this->at(1))
            ->method('parse')
            ->with($productUnitExpression)
            ->willReturn($parsedUnitExpression);

        $this->parser->expects($this->at(2))
            ->method('parse')
            ->with($ruleExpression)
            ->willReturn($parsedRuleExpression);

        $this->parser->expects($this->at(3))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
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
                'message' => 'oro.pricing.validators.field_is_not_allowed_as.message',
                'messageParams' => [
                    '%fieldName%' => 'quantity',
                    '%inputName%' => 'oro.pricing.pricerule.product_unit.label' . $this->translatedLabel
                ],
                'parsedUnitExpression' => new RelationNode(Product::class, 'msrp', 'quantity'),
                'parsedRuleExpression' => new RelationNode(Product::class, 'msrp', 'value')
            ],
            'test_relation_exists_in_rule_condition' => [
                'productUnitExpression' => 'product.msrp.unit',
                'ruleExpression' => 'product.map.value',
                'message' => 'oro.pricing.validators.relation_not_in_rule.message',
                'messageParams' => [
                    '%relationName%' => 'msrp',
                    '%fieldName%' => 'oro.pricing.pricerule.product_unit.label' . $this->translatedLabel
                ],
                'parsedUnitExpression' => new RelationNode(Product::class, 'msrp', 'unit'),
                'parsedRuleExpression' => new RelationNode(Product::class, 'map', 'value')
            ],
        ];
    }

    /**
     * @dataProvider validateProductUnitWithInvalidNodeRelationFieldFailDataProvider
     *
     * @param string $productUnitExpression
     * @param string $ruleExpression
     * @param NodeInterface $parsedUnitExpression
     * @param string $message
     * @param array $messageParams
     */
    public function testValidateProductUnitFailWithInvalidNodeRelationField(
        $productUnitExpression,
        $ruleExpression,
        $message,
        array $messageParams,
        NodeInterface $parsedUnitExpression
    ) {
        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->with(Product::class, 'msrp')
            ->willReturn(PriceAttributeProductPrice::class);

        $rule = new PriceRule();
        $rule->setProductUnitExpression($productUnitExpression)
            ->setRule($ruleExpression);

        $this->parser->expects($this->at(0))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $this->parser->expects($this->at(1))
            ->method('parse')
            ->with($productUnitExpression)
            ->willReturn($parsedUnitExpression);

        $this->parser->expects($this->at(2))
            ->method('parse')
            ->with(null)
            ->willReturn(null);

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
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
    public function validateProductUnitWithInvalidNodeRelationFieldFailDataProvider()
    {
        return [
            'test_relation_not_product_unit_holder' => [
                'productUnitExpression' => 'product.test.unit',
                'ruleExpression' => 'product.msrp.value',
                'message' => 'oro.pricing.validators.field_is_not_allowed_as.message',
                'messageParams' => [
                    '%fieldName%' => 'test',
                    '%inputName%' => 'oro.pricing.pricerule.product_unit.label' . $this->translatedLabel
                ],
                'parsedUnitExpression' => new RelationNode(Product::class, 'test', 'unit')
            ]
        ];
    }
}
