<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Model;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveOrder;

class ApruveOrderTest extends \PHPUnit_Framework_TestCase
{
    const ID = 'sampleId';
    const DATA = [
        'id' => self::ID,
        'merchantId' => 'sampleId',
    ];

    /**
     * @var ApruveOrder
     */
    private $apruveOrder;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->apruveOrder = new ApruveOrder(self::DATA);
    }

    public function testGetData()
    {
        static::assertSame(self::DATA, $this->apruveOrder->getData());
    }

    public function testGetId()
    {
        static::assertSame(self::ID, $this->apruveOrder->getId());
    }
}
