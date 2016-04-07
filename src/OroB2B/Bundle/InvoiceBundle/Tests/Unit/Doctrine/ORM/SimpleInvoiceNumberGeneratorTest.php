<?php

namespace OroB2B\Bundle\InvoiceBundle\Tests\Unit\Doctrine\ORM;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\InvoiceBundle\Doctrine\ORM\SimpleInvoiceNumberGenerator;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;

class SimpleInvoiceNumberGeneratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testGenerate()
    {
        $generator = new SimpleInvoiceNumberGenerator();
        /** @var Invoice $invoice */
        $invoice = $this->getEntity('OroB2B\Bundle\InvoiceBundle\Entity\Invoice', ['id' => 1]);

        $number = $generator->generate($invoice);
        $this->assertSame(1, $number);
    }
}
