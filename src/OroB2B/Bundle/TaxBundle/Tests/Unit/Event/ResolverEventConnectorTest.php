<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Oro\Bundle\TaxBundle\Event\ResolverEventConnector;
use Oro\Bundle\TaxBundle\Event\ResolveTaxEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;
use Oro\Bundle\TaxBundle\Resolver\StopPropagationException;

class ResolverEventConnectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resolver;

    /**
     * @var ResolverEventConnector
     */
    protected $connector;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ResolverInterface $resolver */
        $this->resolver = $this->getMock('Oro\Bundle\TaxBundle\Resolver\ResolverInterface');
        $this->connector = new ResolverEventConnector($this->resolver);
    }

    public function testOnResolve()
    {
        $taxable = new Taxable();
        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->with($taxable);

        $event = new ResolveTaxEvent($taxable);
        $this->connector->onResolve($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnResolveStopPropagation()
    {
        $taxable = new Taxable();
        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->with($taxable)
            ->will($this->throwException(new StopPropagationException));

        $event = new ResolveTaxEvent($taxable);
        $this->connector->onResolve($event);
        $this->assertTrue($event->isPropagationStopped());
    }
}
