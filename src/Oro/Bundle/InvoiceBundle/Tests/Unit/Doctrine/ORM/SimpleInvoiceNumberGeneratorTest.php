<?php

namespace Oro\Bundle\InvoiceBundle\Tests\Unit\Doctrine\ORM;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\InvoiceBundle\Doctrine\ORM\SimpleInvoiceNumberGenerator;
use Oro\Bundle\InvoiceBundle\Entity\Invoice;

class SimpleInvoiceNumberGeneratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testGenerate()
    {
        $generator = new SimpleInvoiceNumberGenerator();
        /** @var Invoice $invoice */
        $invoice = $this->getEntity('Oro\Bundle\InvoiceBundle\Entity\Invoice', ['id' => 1]);

        $number = $generator->generate($invoice);
        $this->assertSame(1, $number);
    }
}
