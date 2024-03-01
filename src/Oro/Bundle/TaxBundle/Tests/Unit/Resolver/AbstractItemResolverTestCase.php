<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Matcher\CountryMatcher;
use Oro\Bundle\TaxBundle\Matcher\MatcherInterface;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;
use Oro\Bundle\TaxBundle\Resolver\RowTotalResolver;
use Oro\Bundle\TaxBundle\Resolver\UnitResolver;
use Oro\Bundle\TaxBundle\Tests\ResultComparatorTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class AbstractItemResolverTestCase extends TestCase
{
    use ResultComparatorTrait;

    protected UnitResolver|MockObject $unitResolver;
    protected RowTotalResolver|MockObject $rowTotalResolver;
    protected CountryMatcher|MockObject $matcher;
    protected AbstractItemResolver $resolver;

    protected function setUp(): void
    {
        $this->unitResolver = $this->createMock(UnitResolver::class);
        $this->rowTotalResolver = $this->createMock(RowTotalResolver::class);
        $this->matcher = $this->createMock(MatcherInterface::class);

        $this->resolver = $this->createResolver();
    }

    abstract protected function createResolver(): AbstractItemResolver;

    protected function assertNothing()
    {
        $this->matcher->expects($this->never())
            ->method($this->anything());
        $this->unitResolver->expects($this->never())
            ->method($this->anything());
        $this->rowTotalResolver->expects($this->never())
            ->method($this->anything());
    }

    protected function getTaxRule(string $taxCode, string $taxRate): TaxRule
    {
        $taxRule = new TaxRule();
        $tax = new Tax();
        $tax
            ->setRate($taxRate)
            ->setCode($taxCode);
        $taxRule->setTax($tax);

        return $taxRule;
    }

    protected function assertEmptyResult(Taxable $taxable): void
    {
        $this->assertEquals(new ResultElement(), $taxable->getResult()->getUnit());
        $this->assertEquals(new ResultElement(), $taxable->getResult()->getRow());
        $this->assertEquals([], $taxable->getResult()->getTaxes());
    }
}
