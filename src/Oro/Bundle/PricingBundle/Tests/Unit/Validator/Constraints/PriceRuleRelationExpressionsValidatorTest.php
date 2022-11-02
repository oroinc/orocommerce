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
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PriceRuleRelationExpressionsValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ExpressionParser|\PHPUnit\Framework\MockObject\MockObject */
    private $parser;

    /** @var FieldsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldsProvider;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->parser = $this->createMock(ExpressionParser::class);
        $this->fieldsProvider = $this->createMock(FieldsProviderInterface::class);
        $this->fieldsProvider->expects($this->any())
            ->method('getDetailedFieldsInformation')
            ->willReturn(
                [
                    'msrp' => [
                        'name' => 'msrp',
                        'type' => 'manyToOne',
                        'label' => 'MSRP',
                        'relation_type' => 'manyToOne',
                        'related_entity_name' => PriceAttributeProductPrice::class
                    ]
                ]
            );

        parent::setUp();
    }

    protected function createValidator()
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id) {
                return $id . ' (translated)';
            });

        return new PriceRuleRelationExpressionsValidator(
            $this->parser,
            $this->fieldsProvider,
            $translator
        );
    }

    /**
     * @dataProvider validaDataProvider
     */
    public function testValidateSuccess(
        string $ruleExpression,
        NodeInterface $ruleNode,
        string $currencyExpression,
        NodeInterface $currencyNode,
        string $unitExpression,
        NodeInterface $unitNode,
        string $qtyExpression,
        NodeInterface $qtyNode
    ) {
        $rule = new PriceRule();
        $rule->setRule($ruleExpression)
            ->setCurrencyExpression($currencyExpression)
            ->setProductUnitExpression($unitExpression)
            ->setQuantityExpression($qtyExpression);

        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->willReturnMap([
                [Product::class, 'msrp', PriceAttributeProductPrice::class],
                [PriceAttributeProductPrice::class, 'unit', ProductUnit::class],
                [Product::class, 'primaryUnit', ProductUnitPrecision::class],
                [ProductUnitPrecision::class, 'unit', ProductUnit::class],
            ]);
        $this->fieldsProvider->expects($this->any())
            ->method('isRelation')
            ->willReturnMap([
                [Product::class, 'msrp', true],
                [Product::class, 'primaryUnit', true],
                [PriceAttributeProductPrice::class, 'unit', true],
                [ProductUnitPrecision::class, 'unit', true],
            ]);
        $this->parser->expects($this->any())
            ->method('parse')
            ->willReturnMap([
                [$rule->getCurrencyExpression(), $currencyNode],
                [$rule->getProductUnitExpression(), $unitNode],
                [$rule->getQuantityExpression(), $qtyNode],
                [$rule->getRule(), $ruleNode]
            ]);

        $constraint = new PriceRuleRelationExpressions();
        $this->validator->validate($rule, $constraint);

        $this->assertNoViolation();
    }

    public function validaDataProvider(): array
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
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method($this->anything());

        $rule = new PriceRule();
        $rule->setRule('product.msrp.value')
            ->setProductUnitExpression('pricelist[1].');

        $this->parser->expects($this->any())
            ->method('parse')
            ->willThrowException(new SyntaxError('Expected name around position 14.'));

        $constraint = new PriceRuleRelationExpressions();
        $this->validator->validate($rule, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider validateCurrencyFailDataProvider
     */
    public function testValidateCurrencyFail(
        string $currencyExpression,
        string $message,
        array $messageParams,
        NodeInterface $parsedCurrencyExpression
    ) {
        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->willReturnMap([
                [Product::class, 'msrp', PriceAttributeProductPrice::class],
                [PriceAttributeProductPrice::class, 'currency', null]
            ]);

        $rule = new PriceRule();
        $rule->setCurrencyExpression($currencyExpression);

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive([$currencyExpression], [null], [null])
            ->willReturnOnConsecutiveCalls($parsedCurrencyExpression, null, null);

        $constraint = new PriceRuleRelationExpressions();
        $this->validator->validate($rule, $constraint);

        $this->buildViolation($message)
            ->setParameters($messageParams)
            ->atPath('property.path.currencyExpression')
            ->assertRaised();
    }

    public function validateCurrencyFailDataProvider(): array
    {
        return [
            'test_to_many_nodes' => [
                'currencyExpression' => 'product.msrp.currency + 10',
                'message' => 'oro.pricing.validators.one_expression_allowed.message',
                'messageParams' => ['%fieldName%' => 'oro.pricing.pricerule.currency.label (translated)'],
                'parsedCurrencyExpression' => new BinaryNode(
                    new RelationNode(Product::class, 'msrp', 'quantity'),
                    new ValueNode(10),
                    '+'
                ),
            ],
            'test_is_relation_node' => [
                'currencyExpression' => 'product.id',
                'message' => 'oro.pricing.validators.only_price_relations_available.message',
                'messageParams' => ['%fieldName%' => 'oro.pricing.pricerule.currency.label (translated)'],
                'parsedCurrencyExpression' => new NameNode(Product::class, 'id'),
            ],
        ];
    }

    /**
     * @dataProvider validateCurrencyWithRuleExpressionFailDataProvider
     */
    public function testValidateCurrencyWithRuleExpressionFail(
        string $currencyExpression,
        string $ruleExpression,
        string $message,
        array $messageParams,
        NodeInterface $parsedCurrencyExpression,
        NodeInterface $parsedRuleExpression = null
    ) {
        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->willReturnMap([
                [Product::class, 'msrp', PriceAttributeProductPrice::class],
                [PriceAttributeProductPrice::class, 'currency', null]
            ]);

        $rule = new PriceRule();
        $rule->setCurrencyExpression($currencyExpression)
            ->setRule($ruleExpression);

        $this->parser->expects($this->exactly(4))
            ->method('parse')
            ->withConsecutive([$currencyExpression], [$ruleExpression], [null], [null])
            ->willReturnOnConsecutiveCalls($parsedCurrencyExpression, $parsedRuleExpression, null, null);

        $constraint = new PriceRuleRelationExpressions();
        $this->validator->validate($rule, $constraint);

        $this->buildViolation($message)
            ->setParameters($messageParams)
            ->atPath('property.path.currencyExpression')
            ->assertRaised();
    }

    public function validateCurrencyWithRuleExpressionFailDataProvider(): array
    {
        return [
            'test_relation_field_is_not_currency' => [
                'currencyExpression' => 'product.msrp.quantity',
                'ruleExpression' => 'product.msrp.value',
                'message' => 'oro.pricing.validators.field_is_not_allowed_as.message',
                'messageParams' => [
                    '%fieldName%' => 'quantity',
                    '%inputName%' => 'oro.pricing.pricerule.currency.label (translated)'
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
                    '%fieldName%' => 'oro.pricing.pricerule.currency.label (translated)'
                ],
                'parsedCurrencyExpression' => new RelationNode(Product::class, 'msrp', 'currency'),
                'parsedRuleExpression' => new RelationNode(Product::class, 'map', 'value'),
            ],
        ];
    }

    /**
     * @dataProvider validateQuantityFailDataProvider
     */
    public function testValidateQuantityFail(
        string $quantityExpression,
        string $message,
        array $messageParams,
        NodeInterface $parsedQuantityExpression
    ) {
        $rule = new PriceRule();
        $rule->setQuantityExpression($quantityExpression);

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive([null], [null], [$quantityExpression])
            ->willReturnOnConsecutiveCalls(null, null, $parsedQuantityExpression);

        $constraint = new PriceRuleRelationExpressions();
        $this->validator->validate($rule, $constraint);

        $this->buildViolation($message)
            ->setParameters($messageParams)
            ->atPath('property.path.quantityExpression')
            ->assertRaised();
    }

    public function validateQuantityFailDataProvider(): array
    {
        return [
            'test_more_then_one_relation_node' => [
                'quantityExpression' => 'product.msrp.quantity + product.map.quantity',
                'message' => 'oro.pricing.validators.too_many_relations.message',
                'messageParams' => ['%fieldName%' => 'oro.pricing.pricerule.quantity.label (translated)'],
                'parsedQuantityExpression' => new BinaryNode(
                    new RelationNode(Product::class, 'msrp', 'quantity'),
                    new RelationNode(Product::class, 'map', 'quantity'),
                    '+'
                ),
            ],
            'test_name_node' => [
                'quantityExpression' => 'product.id + 10',
                'message' => 'oro.pricing.validators.too_many_relations.message',
                'messageParams' => ['%fieldName%' => 'oro.pricing.pricerule.quantity.label (translated)'],
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
     */
    public function testValidateQuantityWithRuleExpressionFail(
        string $quantityExpression,
        string $ruleExpression,
        string $message,
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

        $this->parser->expects($this->exactly(4))
            ->method('parse')
            ->withConsecutive([null], [null], [$quantityExpression], [$ruleExpression])
            ->willReturnOnConsecutiveCalls(null, null, $parsedQuantityExpression, $parsedRuleExpression);

        $constraint = new PriceRuleRelationExpressions();
        $this->validator->validate($rule, $constraint);

        $this->buildViolation($message)
            ->setParameters($messageParams)
            ->atPath('property.path.quantityExpression')
            ->assertRaised();
    }

    public function validateQuantityWithRuleExpressionFailDataProvider(): array
    {
        return [
            'test_relation_exists_in_rule_condition' => [
                'quantityExpression' => 'product.msrp.quantity',
                'ruleExpression' => 'product.map.value',
                'message' => 'oro.pricing.validators.relation_not_in_rule.message',
                'messageParams' => [
                    '%relationName%' => 'msrp',
                    '%fieldName%' => 'oro.pricing.pricerule.quantity.label (translated)'
                ],
                'parsedQuantityExpression' => new RelationNode(Product::class, 'msrp', 'quantity'),
                'parsedRuleExpression' => new RelationNode(Product::class, 'map', 'value'),
            ],
        ];
    }

    /**
     * @dataProvider validateProductUnitFailDataProvider
     */
    public function testValidateProductUnitFail(
        string $productUnitExpression,
        string $message,
        array $messageParams,
        NodeInterface $parsedUnitExpression
    ) {
        $this->fieldsProvider->expects($this->any())
            ->method('getRealClassName')
            ->with(Product::class, 'msrp')
            ->willReturn(PriceAttributeProductPrice::class);

        $rule = new PriceRule();
        $rule->setProductUnitExpression($productUnitExpression);

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive([null], [$productUnitExpression], [null])
            ->willReturnOnConsecutiveCalls(null, $parsedUnitExpression, null);

        $constraint = new PriceRuleRelationExpressions();
        $this->validator->validate($rule, $constraint);

        $this->buildViolation($message)
            ->setParameters($messageParams)
            ->atPath('property.path.productUnitExpression')
            ->assertRaised();
    }

    public function validateProductUnitFailDataProvider(): array
    {
        return [
            'test_to_many_nodes' => [
                'productUnitExpression' => 'product.msrp.unit + 10',
                'message' => 'oro.pricing.validators.one_expression_allowed.message',
                'messageParams' => [
                    '%fieldName%' => 'oro.pricing.pricerule.product_unit.label (translated)'
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
                    '%fieldName%' => 'oro.pricing.pricerule.product_unit.label (translated)'
                ],
                'parsedUnitExpression' => new NameNode(Product::class, 'id'),
            ],
        ];
    }

    /**
     * @dataProvider validateProductUnitWithRuleExpressionFailDataProvider
     */
    public function testValidateProductUnitFailWithRuleExpression(
        string $productUnitExpression,
        string $ruleExpression,
        string $message,
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

        $this->parser->expects($this->exactly(4))
            ->method('parse')
            ->withConsecutive([null], [$productUnitExpression], [$ruleExpression], [null])
            ->willReturnOnConsecutiveCalls(null, $parsedUnitExpression, $parsedRuleExpression, null);

        $constraint = new PriceRuleRelationExpressions();
        $this->validator->validate($rule, $constraint);

        $this->buildViolation($message)
            ->setParameters($messageParams)
            ->atPath('property.path.productUnitExpression')
            ->assertRaised();
    }

    public function validateProductUnitWithRuleExpressionFailDataProvider(): array
    {
        return [
            'test_relation_not_product_unit_holder' => [
                'productUnitExpression' => 'product.msrp.quantity',
                'ruleExpression' => 'product.msrp.value',
                'message' => 'oro.pricing.validators.field_is_not_allowed_as.message',
                'messageParams' => [
                    '%fieldName%' => 'quantity',
                    '%inputName%' => 'oro.pricing.pricerule.product_unit.label (translated)'
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
                    '%fieldName%' => 'oro.pricing.pricerule.product_unit.label (translated)'
                ],
                'parsedUnitExpression' => new RelationNode(Product::class, 'msrp', 'unit'),
                'parsedRuleExpression' => new RelationNode(Product::class, 'map', 'value')
            ],
        ];
    }

    /**
     * @dataProvider validateProductUnitWithInvalidNodeRelationFieldFailDataProvider
     */
    public function testValidateProductUnitFailWithInvalidNodeRelationField(
        string $productUnitExpression,
        string $ruleExpression,
        string $message,
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

        $this->parser->expects($this->exactly(3))
            ->method('parse')
            ->withConsecutive([null], [$productUnitExpression], [null])
            ->willReturnOnConsecutiveCalls(null, $parsedUnitExpression, null);

        $constraint = new PriceRuleRelationExpressions();
        $this->validator->validate($rule, $constraint);

        $this->buildViolation($message)
            ->setParameters($messageParams)
            ->atPath('property.path.productUnitExpression')
            ->assertRaised();
    }

    public function validateProductUnitWithInvalidNodeRelationFieldFailDataProvider(): array
    {
        return [
            'test_relation_not_product_unit_holder' => [
                'productUnitExpression' => 'product.test.unit',
                'ruleExpression' => 'product.msrp.value',
                'message' => 'oro.pricing.validators.field_is_not_allowed_as.message',
                'messageParams' => [
                    '%fieldName%' => 'test',
                    '%inputName%' => 'oro.pricing.pricerule.product_unit.label (translated)'
                ],
                'parsedUnitExpression' => new RelationNode(Product::class, 'test', 'unit')
            ]
        ];
    }
}
