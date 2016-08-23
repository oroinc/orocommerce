<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Brick\Math\BigDecimal;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Matcher\MatcherInterface;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;
use Oro\Bundle\TaxBundle\Resolver\RowTotalResolver;
use Oro\Bundle\TaxBundle\Resolver\UnitResolver;
use Oro\Bundle\TaxBundle\Tests\ResultComparatorTrait;

abstract class AbstractItemResolverTestCase extends \PHPUnit_Framework_TestCase
{
    use ResultComparatorTrait;

    /** @var AbstractItemResolver */
    protected $resolver;

    /**
     * @var UnitResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $unitResolver;

    /**
     * @var RowTotalResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rowTotalResolver;

    /**
     * @var MatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $matcher;

    protected function setUp()
    {
        $this->unitResolver = $this->getMockBuilder('Oro\Bundle\TaxBundle\Resolver\UnitResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->rowTotalResolver = $this->getMockBuilder('Oro\Bundle\TaxBundle\Resolver\RowTotalResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcher = $this->getMock('Oro\Bundle\TaxBundle\Matcher\MatcherInterface');

        $this->resolver = $this->createResolver();
    }

    /** @return AbstractItemResolver */
    abstract protected function createResolver();

    protected function assertNothing()
    {
        $this->matcher->expects($this->never())->method($this->anything());
        $this->unitResolver->expects($this->never())->method($this->anything());
        $this->rowTotalResolver->expects($this->never())->method($this->anything());
    }

    /**
     * @param string $taxCode
     * @param string $taxRate
     * @return TaxRule
     */
    protected function getTaxRule($taxCode, $taxRate)
    {
        $taxRule = new TaxRule();
        $tax = new Tax();
        $tax
            ->setRate($taxRate)
            ->setCode($taxCode);
        $taxRule->setTax($tax);

        return $taxRule;
    }

    /**
     * @return Taxable
     */
    abstract protected function getTaxable();

    /**
     * @param Taxable $taxable
     */
    abstract protected function assertEmptyResult(Taxable $taxable);

    public function testDestinationMissing()
    {
        $taxable = $this->getTaxable();
        $taxable->setPrice('1');
        $taxable->setAmount('1');

        $this->assertNothing();

        $this->resolver->resolve($taxable);

        $this->assertEmptyResult($taxable);
    }

    public function testEmptyAmount()
    {
        $taxable = $this->getTaxable();

        $this->assertNothing();

        $this->resolver->resolve($taxable);

        $this->assertEmptyResult($taxable);
    }

    public function testEmptyRules()
    {
        $taxable = $this->getTaxable();
        $taxable->setTaxationAddress(new OrderAddress());
        $taxable->setPrice('1');
        $taxable->setAmount('1');

        $taxableUnitPrice = BigDecimal::of($taxable->getPrice());
        $taxableAmount = $taxableUnitPrice->multipliedBy($taxable->getQuantity());

        $this->matcher->expects($this->once())->method('match')->willReturn([]);

        $this->unitResolver->expects($this->once())
            ->method('resolveUnitPrice')
            ->with($taxable->getResult(), [], $taxableUnitPrice);

        $this->rowTotalResolver->expects($this->once())
            ->method('resolveRowTotal')
            ->with($taxable->getResult(), [], $taxableAmount);

        $this->resolver->resolve($taxable);

        $this->assertEquals([], $taxable->getResult()->getTaxes());
    }

    /**
     * @dataProvider rulesDataProvider
     * @param string $taxableAmount
     * @param array $taxRules
     */
    public function testRules($taxableAmount, array $taxRules)
    {
        $taxable = $this->getTaxable();
        $taxable->setPrice($taxableAmount);
        $taxable->setQuantity(3);
        $taxable->setAmount($taxableAmount);
        $taxable->setTaxationAddress(new OrderAddress());
        $taxable->getContext()->offsetSet(Taxable::PRODUCT_TAX_CODE, 'prod_tax_code');
        $taxable->getContext()->offsetSet(Taxable::ACCOUNT_TAX_CODE, 'acc_tax_code');

        $taxableUnitPrice = BigDecimal::of($taxable->getPrice());

        $this->matcher->expects($this->once())->method('match')->willReturn($taxRules);

        $this->unitResolver->expects($this->once())
            ->method('resolveUnitPrice')
            ->with($taxable->getResult(), $taxRules, $taxableUnitPrice);

        $this->rowTotalResolver->expects($this->once())
            ->method('resolveRowTotal')
            ->with($taxable->getResult(), $taxRules, $taxableUnitPrice, $taxable->getQuantity());

        $this->resolver->resolve($taxable);
    }

    /**
     * @return array
     */
    abstract public function rulesDataProvider();
}
