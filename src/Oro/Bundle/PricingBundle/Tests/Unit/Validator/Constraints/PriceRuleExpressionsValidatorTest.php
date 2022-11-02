<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Compiler\PriceListRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceRuleExpressions;
use Oro\Bundle\PricingBundle\Validator\Constraints\PriceRuleExpressionsValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PriceRuleExpressionsValidatorTest extends ConstraintValidatorTestCase
{
    /** @var PriceListRuleCompiler|\PHPUnit\Framework\MockObject\MockObject */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = $this->createMock(PriceListRuleCompiler::class);
        parent::setUp();
    }

    protected function createValidator(): PriceRuleExpressionsValidator
    {
        return new PriceRuleExpressionsValidator($this->compiler);
    }

    public function testPriceListWithEmptyRule()
    {
        $priceRule = new PriceRule();

        $this->compiler->expects($this->never())
            ->method($this->anything());

        $constraint = new PriceRuleExpressions();
        $this->validator->validate($priceRule, $constraint);
        $this->assertNoViolation();
    }

    public function testPriceListWithValidRule()
    {
        $priceRule = new PriceRule();
        $priceRule->setRule('product.id');
        $priceRule->setRuleCondition('product.id > 0');

        $query1 = $this->createMock(AbstractQuery::class);
        $query1->expects($this->once())
            ->method('getResult');

        $qb1 = $this->createMock(QueryBuilder::class);
        $qb1->expects($this->once())
            ->method('getQuery')
            ->willReturn($query1);

        $query2 = $this->createMock(AbstractQuery::class);
        $query2->expects($this->once())
            ->method('getResult');
        $qb2 = $this->createMock(QueryBuilder::class);
        $qb2->expects($this->once())
            ->method('getQuery')
            ->willReturn($query2);

        $this->compiler->expects($this->exactly(2))
            ->method('compileQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $qb1,
                $qb2
            );

        $constraint = new PriceRuleExpressions();
        $this->validator->validate($priceRule, $constraint);
        $this->assertNoViolation();
    }

    public function testPriceListWithValidCalculateAs()
    {
        $priceRule = new PriceRule();
        $priceRule->setRule('product.id');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->compiler->expects($this->once())
            ->method('compileQueryBuilder')
            ->willReturn($qb);

        $constraint = new PriceRuleExpressions();
        $this->validator->validate($priceRule, $constraint);
        $this->assertNoViolation();
    }

    public function testPriceListWithInvalidCalculateAs()
    {
        $priceRule = new PriceRule();
        $priceRule->setRule('product.id * "a"');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willThrowException(new Exception('Something went wrong'));

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->compiler->expects($this->once())
            ->method('compileQueryBuilder')
            ->willReturn($qb);

        $constraint = new PriceRuleExpressions();
        $this->validator->validate($priceRule, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.rule')
            ->assertRaised();
    }

    public function testPriceListWithValidRuleCondition()
    {
        $priceRule = new PriceRule();
        $priceRule->setRuleCondition('product.id > 0');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->compiler->expects($this->once())
            ->method('compileQueryBuilder')
            ->willReturn($qb);

        $constraint = new PriceRuleExpressions();
        $this->validator->validate($priceRule, $constraint);
        $this->assertNoViolation();
    }

    public function testPriceListWithInvalidRuleCondition()
    {
        $priceRule = new PriceRule();
        $priceRule->setRuleCondition('product.id > "a"');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willThrowException(new Exception('Something went wrong'));

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->compiler->expects($this->once())
            ->method('compileQueryBuilder')
            ->willReturn($qb);

        $constraint = new PriceRuleExpressions();
        $this->validator->validate($priceRule, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.ruleCondition')
            ->assertRaised();
    }
}
