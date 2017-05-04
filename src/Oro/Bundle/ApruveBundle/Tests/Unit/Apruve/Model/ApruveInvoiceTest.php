<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Model;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveInvoice;

class ApruveInvoiceTest extends \PHPUnit_Framework_TestCase
{
    const ID = 'sampleId';
    const DATA = [
        'id' => self::ID,
        'amount_cents' => 1000,
    ];

    /**
     * @var ApruveInvoice
     */
    private $apruveInvoice;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->apruveInvoice = new ApruveInvoice(self::DATA);
    }

    public function testGetData()
    {
        static::assertSame(self::DATA, $this->apruveInvoice->getData());
    }

    public function testGetId()
    {
        static::assertSame(self::ID, $this->apruveInvoice->getId());
    }
}
