<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Shipment;

use Oro\Bundle\ApruveBundle\Apruve\Factory\Shipment\ApruveShipmentFromResponseFactory;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

class ApruveShipmentFromResponseFactoryTest extends \PHPUnit_Framework_TestCase
{
    const APRUVE_SHIPMENT = [
        'amount_cents' => 1000,
        'currency' => 'USD',
        'shipment_items' => [
            [
                'title' => 'Sample title',
                'amount_cents' => 1000,
                'currency' => 'USD',
                'quantity' => 1,
            ]
        ],
    ];

    /**
     * @var RestResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $restResponse;

    /**
     * @var ApruveShipmentFromResponseFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->restResponse = $this->createMock(RestResponseInterface::class);

        $this->factory = new ApruveShipmentFromResponseFactory();
    }

    public function testCreateFromResponse()
    {
        $this->restResponse
            ->expects(static::once())
            ->method('json')
            ->willReturn(self::APRUVE_SHIPMENT);

        $actual = $this->factory->createFromResponse($this->restResponse);

        static::assertEquals(self::APRUVE_SHIPMENT, $actual->getData());
    }
}
