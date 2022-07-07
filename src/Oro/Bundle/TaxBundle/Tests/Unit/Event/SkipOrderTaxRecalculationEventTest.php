<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Event;

use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class SkipOrderTaxRecalculationEventTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $taxable = new Taxable();
        $properties = [
            ['taxable', $taxable, false],
            ['skipOrderTaxRecalculation', true, true],
        ];

        $event = new SkipOrderTaxRecalculationEvent($taxable);

        self::assertPropertyAccessors($event, $properties);
    }

    public function testSetSkipOrderTaxRecalculation(): void
    {
        $taxable = new Taxable();

        $event = new SkipOrderTaxRecalculationEvent($taxable);
        $event->setSkipOrderTaxRecalculation(false);

        self::assertTrue($event->isPropagationStopped());
    }
}
