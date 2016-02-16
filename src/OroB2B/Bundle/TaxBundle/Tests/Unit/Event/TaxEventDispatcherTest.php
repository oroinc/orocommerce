<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent;
use OroB2B\Bundle\TaxBundle\Event\TaxEventDispatcher;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

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
                [ResolveTaxEvent::RESOLVE_BEFORE, $this->isInstanceOf('OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent')],
                [ResolveTaxEvent::RESOLVE, $this->isInstanceOf('OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent')],
                [ResolveTaxEvent::RESOLVE_AFTER, $this->isInstanceOf('OroB2B\Bundle\TaxBundle\Event\ResolveTaxEvent')]
            );

        $taxDispatcher->dispatch($taxable);
    }
}
