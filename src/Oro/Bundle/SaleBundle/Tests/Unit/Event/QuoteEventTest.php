<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Event;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Symfony\Component\Form\FormInterface;

class QuoteEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $quote = new Quote();
        $form = $this->createMock(FormInterface::class);
        $submittedData = ['test'];

        $event = new QuoteEvent($form, $quote, $submittedData);

        self::assertSame($form, $event->getForm());
        self::assertSame($quote, $event->getQuote());
        self::assertSame($submittedData, $event->getSubmittedData());
        self::assertInstanceOf(\ArrayObject::class, $event->getData());
    }
}
