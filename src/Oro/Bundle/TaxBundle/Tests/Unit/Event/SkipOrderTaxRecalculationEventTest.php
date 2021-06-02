<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Event;

use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class SkipOrderTaxRecalculationEventTest extends \PHPUnit\Framework\TestCase
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
}
