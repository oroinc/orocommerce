<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener;

use OroB2B\Bundle\TaxBundle\Event\ResolverEventConnector;
use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface;

class ResolverEventConnectorTest extends \PHPUnit_Framework_TestCase
{
    public function testOnResolve()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ResolverInterface $resolver */
        $resolver = $this->getMock('OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface');

        $taxable = new Taxable();

        $resolver->expects($this->once())->method('resolve')->with($taxable);

        $connector = new ResolverEventConnector($resolver);
        $connector->onResolve(new ResolveTaxEvent($taxable));
    }
}
