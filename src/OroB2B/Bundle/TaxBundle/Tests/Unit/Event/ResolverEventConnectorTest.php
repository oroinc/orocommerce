<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\TaxBundle\Event\ResolverEventConnector;
use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface;
use OroB2B\Bundle\TaxBundle\Resolver\StopPropagationException;

class ResolverEventConnectorTest extends \PHPUnit_Framework_TestCase
{
    public function testOnResolve()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ResolverInterface $resolver */
        $resolver = $this->getMock('OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface');

        $taxable = new Taxable();

        /** @var ResolveTaxEvent|\PHPUnit_Framework_MockObject_MockObject $resolverTaxEvent */
        $resolverTaxEvent = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent')
            ->setConstructorArgs([$taxable])
            ->getMock();

        $resolverTaxEvent->expects($this->once())
            ->method('getTaxable')
            ->willReturn($taxable);

        $resolverTaxEvent->expects($this->never())->method('stopPropagation');

        $resolver->expects($this->once())->method('resolve')->with($taxable);

        $connector = new ResolverEventConnector($resolver);
        $connector->onResolve($resolverTaxEvent);
    }

    public function testOnResolveWithException()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ResolverInterface $resolver */
        $resolver = $this->getMock('OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface');

        $taxable = new Taxable();

        /** @var ResolveTaxEvent|\PHPUnit_Framework_MockObject_MockObject $resolverTaxEvent */
        $resolverTaxEvent = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent')
            ->setConstructorArgs([$taxable])
            ->getMock();

        $resolverTaxEvent->expects($this->once())
            ->method('getTaxable')
            ->willReturn($taxable);

        $resolverTaxEvent->expects($this->once())->method('stopPropagation');

        $resolver->expects($this->once())
            ->method('resolve')
            ->with($taxable)
            ->willThrowException(new StopPropagationException());

        $resolverTaxEvent->expects($this->once())->method('stopPropagation');

        $connector = new ResolverEventConnector($resolver);
        $connector->onResolve($resolverTaxEvent);
    }
}
