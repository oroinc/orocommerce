<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Event;

use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use PHPUnit\Framework\TestCase;

class LineItemValidateEventTest extends TestCase
{
    private LineItemValidateEvent $lineItemValidateEvent;

    protected function setUp(): void
    {
        $this->lineItemValidateEvent = new LineItemValidateEvent([], []);
    }

    public function testAddErrorByUnit(): void
    {
        $this->lineItemValidateEvent->addErrorByUnit('testSku', 'item', 'testMessage');
        $errors = $this->lineItemValidateEvent->getErrors();
        self::assertCount(1, $errors);
        self::assertArrayHasKey('sku', $errors[0]);
        self::assertEquals('testSku', $errors[0]['sku']);
        self::assertArrayHasKey('unit', $errors[0]);
        self::assertEquals('item', $errors[0]['unit']);
        self::assertArrayHasKey('message', $errors[0]);
        self::assertEquals('testMessage', $errors[0]['message']);
    }

    public function testAddErrorByUnitWithChecksum(): void
    {
        $this->lineItemValidateEvent->addErrorByUnit('testSku', 'item', 'testMessage', 'sampleChecksum');
        $errors = $this->lineItemValidateEvent->getErrors();
        self::assertCount(1, $errors);
        self::assertArrayHasKey('sku', $errors[0]);
        self::assertEquals('testSku', $errors[0]['sku']);
        self::assertArrayHasKey('unit', $errors[0]);
        self::assertEquals('item', $errors[0]['unit']);
        self::assertArrayHasKey('message', $errors[0]);
        self::assertEquals('testMessage', $errors[0]['message']);
        self::assertEquals('sampleChecksum', $errors[0]['checksum']);
    }

    public function testHasErrors(): void
    {
        self::assertFalse($this->lineItemValidateEvent->hasErrors());
        $this->lineItemValidateEvent->addErrorByUnit('testSku', 'item', 'testMessage');
        self::assertTrue($this->lineItemValidateEvent->hasErrors());
    }

    public function testAddWarningByUnit(): void
    {
        $this->lineItemValidateEvent->addWarningByUnit('testSku', 'item', 'testMessage');
        $warnings = $this->lineItemValidateEvent->getWarnings();
        self::assertCount(1, $warnings);
        self::assertArrayHasKey('sku', $warnings[0]);
        self::assertEquals('testSku', $warnings[0]['sku']);
        self::assertArrayHasKey('unit', $warnings[0]);
        self::assertEquals('item', $warnings[0]['unit']);
        self::assertArrayHasKey('message', $warnings[0]);
        self::assertEquals('testMessage', $warnings[0]['message']);
    }

    public function testAddWarningByUnitWithChecksum(): void
    {
        $this->lineItemValidateEvent->addWarningByUnit('testSku', 'item', 'testMessage', 'sampleChecksum');
        $warnings = $this->lineItemValidateEvent->getWarnings();
        self::assertCount(1, $warnings);
        self::assertArrayHasKey('sku', $warnings[0]);
        self::assertEquals('testSku', $warnings[0]['sku']);
        self::assertArrayHasKey('unit', $warnings[0]);
        self::assertEquals('item', $warnings[0]['unit']);
        self::assertArrayHasKey('message', $warnings[0]);
        self::assertEquals('testMessage', $warnings[0]['message']);
        self::assertEquals('sampleChecksum', $warnings[0]['checksum']);
    }

    public function testHasWarnings(): void
    {
        self::assertFalse($this->lineItemValidateEvent->hasWarnings());
        $this->lineItemValidateEvent->addWarningByUnit('testSku', 'item', 'testMessage');
        self::assertTrue($this->lineItemValidateEvent->hasWarnings());
    }
}
