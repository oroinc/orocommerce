<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Resolver;

use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\ShippingResolver;

class ShippingResolverTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShippingResolver */
    protected $resolver;

    protected function setUp()
    {
        $this->resolver = new ShippingResolver();
    }

    public function testResolve()
    {
        $taxable = new Taxable();
        $event = new ResolveTaxEvent($taxable);

        $this->resolver->resolve($event);

        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\Result', $taxable->getResult());
        $this->assertInstanceOf('OroB2B\Bundle\TaxBundle\Model\ResultElement', $taxable->getResult()->getShipping());
    }
}
