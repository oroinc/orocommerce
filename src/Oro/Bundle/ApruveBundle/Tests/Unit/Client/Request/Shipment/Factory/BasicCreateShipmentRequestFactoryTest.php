<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Client\Request\Shipment;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;
use Oro\Bundle\ApruveBundle\Client\Request\Shipment\Factory\BasicCreateShipmentRequestFactory;

class BasicCreateShipmentRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApruveShipment|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveShipment;

    /**
     * @var BasicCreateShipmentRequestFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->apruveShipment = $this->createMock(ApruveShipment::class);
        $this->factory = new BasicCreateShipmentRequestFactory();
    }

    public function testCreate()
    {
        $apruveOrderId = '2124';
        $request = new ApruveRequest('POST', '/invoices/2124/shipments', $this->apruveShipment);

        $actual = $this->factory->create($this->apruveShipment, $apruveOrderId);

        static::assertEquals($request, $actual);
    }
}
