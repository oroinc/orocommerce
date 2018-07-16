<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Event;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;

class QuoteEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $quote = new Quote();
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->getMock();
        $submittedData = ['test'];

        $event = new QuoteEvent($form, $quote, $submittedData);

        static::assertSame($form, $event->getForm());
        static::assertSame($quote, $event->getQuote());
        static::assertSame($submittedData, $event->getSubmittedData());
        static::assertInstanceOf(\ArrayObject::class, $event->getData());
    }
}
