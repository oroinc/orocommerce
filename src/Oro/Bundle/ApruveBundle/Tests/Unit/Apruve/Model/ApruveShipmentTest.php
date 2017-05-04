<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Model;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;

class ApruveShipmentTest extends \PHPUnit_Framework_TestCase
{
    const ID = 'sampleId';
    const DATA = [
        'id' => self::ID,
        'amount_cents' => 1000,
    ];

    /**
     * @var ApruveShipment
     */
    private $apruveShipment;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->apruveShipment = new ApruveShipment(self::DATA);
    }

    public function testGetData()
    {
        static::assertSame(self::DATA, $this->apruveShipment->getData());
    }

    public function testGetId()
    {
        static::assertSame(self::ID, $this->apruveShipment->getId());
    }
}
