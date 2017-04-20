<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Model\Order;

use Oro\Bundle\ApruveBundle\Apruve\Model\AbstractApruveEntity;
use Oro\Bundle\ApruveBundle\Apruve\Model\Order\ApruveOrder;

class ApruveOrderTest extends \PHPUnit_Framework_TestCase
{
    const DATA = [
        'merchantId' => 'sampleId',
    ];

    /**
     * @var AbstractApruveEntity
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
}
