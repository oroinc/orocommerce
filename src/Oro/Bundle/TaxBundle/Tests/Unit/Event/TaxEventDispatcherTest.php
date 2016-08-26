<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\TaxBundle\Event\ResolveTaxEvent;
use Oro\Bundle\TaxBundle\Event\TaxEventDispatcher;
use Oro\Bundle\TaxBundle\Model\Taxable;

class TaxEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatch()
    {
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher */
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $taxDispatcher = new TaxEventDispatcher($eventDispatcher);
        $taxable = new Taxable();

        $eventDispatcher->expects($this->exactly(3))->method('dispatch')
            ->withConsecutive(
                [ResolveTaxEvent::RESOLVE_BEFORE, $this->isInstanceOf('Oro\Bundle\TaxBundle\Event\ResolveTaxEvent')],
                [ResolveTaxEvent::RESOLVE, $this->isInstanceOf('Oro\Bundle\TaxBundle\Event\ResolveTaxEvent')],
                [ResolveTaxEvent::RESOLVE_AFTER, $this->isInstanceOf('Oro\Bundle\TaxBundle\Event\ResolveTaxEvent')]
            );

        $taxDispatcher->dispatch($taxable);
    }
}
