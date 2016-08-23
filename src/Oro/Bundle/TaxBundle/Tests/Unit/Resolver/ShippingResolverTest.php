<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Resolver;

use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ShippingResolver;

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

        $this->resolver->resolve($taxable);

        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Result', $taxable->getResult());
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\ResultElement', $taxable->getResult()->getShipping());
    }

    public function testResolveItem()
    {
        $taxable = new Taxable();
        $taxable->addItem(new Taxable());

        $this->resolver->resolve($taxable);

        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Result', $taxable->getResult());
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\ResultElement', $taxable->getResult()->getShipping());
    }
}
