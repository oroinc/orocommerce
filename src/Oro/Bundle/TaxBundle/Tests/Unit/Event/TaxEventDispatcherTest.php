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
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $taxDispatcher = new TaxEventDispatcher($eventDispatcher);
        $taxable = new Taxable();

        $eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$this->isInstanceOf(ResolveTaxEvent::class), ResolveTaxEvent::RESOLVE_BEFORE],
                [$this->isInstanceOf(ResolveTaxEvent::class), ResolveTaxEvent::RESOLVE],
                [$this->isInstanceOf(ResolveTaxEvent::class), ResolveTaxEvent::RESOLVE_AFTER]
            );

        $taxDispatcher->dispatch($taxable);
    }
}
