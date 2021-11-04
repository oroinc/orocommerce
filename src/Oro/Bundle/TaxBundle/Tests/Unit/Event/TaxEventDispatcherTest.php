<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Event;

use Oro\Bundle\TaxBundle\Event\ResolveTaxEvent;
use Oro\Bundle\TaxBundle\Event\TaxEventDispatcher;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaxEventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    public function testDispatch()
    {
        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $taxDispatcher = new TaxEventDispatcher($eventDispatcher);
        $taxable = new Taxable();

        $eventDispatcher->expects($this->exactly(3))->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf('Oro\Bundle\TaxBundle\Event\ResolveTaxEvent'), ResolveTaxEvent::RESOLVE_BEFORE],
                [$this->isInstanceOf('Oro\Bundle\TaxBundle\Event\ResolveTaxEvent'), ResolveTaxEvent::RESOLVE],
                [$this->isInstanceOf('Oro\Bundle\TaxBundle\Event\ResolveTaxEvent'), ResolveTaxEvent::RESOLVE_AFTER]
            );

        $taxDispatcher->dispatch($taxable);
    }
}
