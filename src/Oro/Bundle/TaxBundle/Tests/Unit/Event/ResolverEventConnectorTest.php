<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Event;

use Oro\Bundle\TaxBundle\Event\ResolverEventConnector;
use Oro\Bundle\TaxBundle\Event\ResolveTaxEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;
use Oro\Bundle\TaxBundle\Resolver\StopPropagationException;

class ResolverEventConnectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ResolverInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $resolver;

    /**
     * @var ResolverEventConnector
     */
    protected $connector;

    protected function setUp(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResolverInterface $resolver */
        $this->resolver = $this->createMock('Oro\Bundle\TaxBundle\Resolver\ResolverInterface');
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
