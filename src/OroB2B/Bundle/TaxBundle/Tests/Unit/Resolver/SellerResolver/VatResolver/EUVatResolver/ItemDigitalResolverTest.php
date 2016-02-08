<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Brick\Math\BigDecimal;

use JMS\Serializer\Tests\Fixtures\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderAddress;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\RowTotalResolver;
use OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver\ItemDigitalResolver;
use OroB2B\Bundle\TaxBundle\Resolver\UnitResolver;

class ItemDigitalResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UnitResolver|
     */
    protected $unitResolver;

    /**
     * @var RowTotalResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rowTotalResolver;

    /**
     * @var CountryMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $matcher;

    /** @var  ItemDigitalResolver|\PHPUnit_Framework_MockObject_MockObject */
    protected $resolver;

    public function setUp()
    {
        $this->unitResolver = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Resolver\UnitResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->rowTotalResolver = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Resolver\RowTotalResolver')
            ->disableOriginalConstructor()
            ->getMock();

        $this->matcher = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $resolver = new ItemDigitalResolver($this->unitResolver, $this->rowTotalResolver);
        $resolver->setMatcher($this->matcher);
        $this->resolver = $resolver;
    }

    /**
     * @dataProvider resolveDataProvider
     * @param string $taxableAmount
     * @param array  $taxRules
     */
    public function testResolve($taxableAmount, array $taxRules)
    {
        $taxable = new Taxable();
        $taxable->setPrice($taxableAmount);
        $taxable->setQuantity(3);
        $taxable->setAmount($taxableAmount);
        $taxable->setDestination(new OrderAddress());
        $taxable->getContext()->offsetSet(Taxable::DIGITAL_PRODUCT, true);

        $this->matcher->expects($this->once())
            ->method('isEuropeanUnionCountry')
            ->willReturn(true);

        $this->matcher->expects($this->once())->method('match')->willReturn($taxRules);

        $taxableUnitPrice = BigDecimal::of($taxable->getPrice());
        $taxableAmount = $taxableUnitPrice->multipliedBy($taxable->getQuantity());

        $this->rowTotalResolver->expects($this->once())
            ->method('resolveRowTotal')
            ->with($taxable->getResult(), $taxRules, $taxableAmount);

        $this->unitResolver->expects($this->once())
            ->method('resolveUnitPrice')
            ->with($taxable->getResult(), $taxRules, $taxableUnitPrice);

        $this->resolver->resolve($taxable);
    }

    /**
     * @return array
     */
    public function resolveDataProvider()
    {
        return [
            [
                '19.99',
                [$this->getTaxRule('city', '0.08')]
            ],
            [
                '19.99',
                [
                    $this->getTaxRule('city', '0.08'),
                    $this->getTaxRule('region', '0.07'),
                ]
            ]
        ];
    }

    public function testResolveWithEmptyTaxable()
    {
        $taxable = new Taxable();

        $this->assertNothing();
        $this->resolver->resolve($taxable);
    }

    public function testResolveWithEmptyAddress()
    {
        $taxable = new Taxable();
        $taxable->setQuantity(3);
        $taxable->setAmount('20');

        $this->assertNothing();
        $this->resolver->resolve($taxable);

        $taxable->addItem(new Taxable());
        $this->resolver->resolve($taxable);

        $taxable->removeItem(new Taxable());
        $taxable->setPrice('20');
        $this->resolver->resolve($taxable);

        $taxable->setOrigin(new OrderAddress());
        $this->resolver->resolve($taxable);
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

    protected function assertNothing()
    {
        $this->matcher->expects($this->never())->method($this->anything());
        $this->unitResolver->expects($this->never())->method($this->anything());
        $this->rowTotalResolver->expects($this->never())->method($this->anything());
    }
}
