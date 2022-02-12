<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Validator\Constraints\ProductAssignmentRuleExpression;
use Oro\Bundle\PricingBundle\Validator\Constraints\ProductAssignmentRuleExpressionValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ProductAssignmentRuleExpressionValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ProductAssignmentRuleCompiler|\PHPUnit\Framework\MockObject\MockObject */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = $this->createMock(ProductAssignmentRuleCompiler::class);
        parent::setUp();
    }

    protected function createValidator(): ProductAssignmentRuleExpressionValidator
    {
        return new ProductAssignmentRuleExpressionValidator($this->compiler);
    }

    public function testPriceListWithEmptyRule()
    {
        $priceList = new PriceList();

        $this->compiler->expects($this->never())
            ->method($this->anything());

        $constraint = new ProductAssignmentRuleExpression();
        $this->validator->validate($priceList, $constraint);
        $this->assertNoViolation();
    }

    public function testPriceListWithValidRule()
    {
        $priceList = new PriceList();
        $priceList->setProductAssignmentRule('product.id > 0');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->compiler->expects($this->once())
            ->method('compileQueryBuilder')
            ->with($priceList)
            ->willReturn($qb);

        $constraint = new ProductAssignmentRuleExpression();
        $this->validator->validate($priceList, $constraint);
        $this->assertNoViolation();
    }

    public function testPriceListWithInvalidRule()
    {
        $priceList = new PriceList();
        $priceList->setProductAssignmentRule('product.id == true');

        $exception = new Exception();
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willThrowException($exception);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->compiler->expects($this->once())
            ->method('compileQueryBuilder')
            ->with($priceList)
            ->willReturn($qb);

        $constraint = new ProductAssignmentRuleExpression();
        $this->validator->validate($priceList, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.productAssignmentRule')
            ->assertRaised();
    }
}
