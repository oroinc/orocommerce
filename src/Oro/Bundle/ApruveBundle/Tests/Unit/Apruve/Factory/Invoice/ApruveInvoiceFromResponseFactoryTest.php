<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Factory\Invoice\ApruveInvoiceFromResponseFactory;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

class ApruveInvoiceFromResponseFactoryTest extends \PHPUnit_Framework_TestCase
{
    const APRUVE_INVOICE = [
        'amount_cents' => 1000,
        'currency' => 'USD',
        'invoice_items' => [
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
     * @var ApruveInvoiceFromResponseFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->restResponse = $this->createMock(RestResponseInterface::class);

        $this->factory = new ApruveInvoiceFromResponseFactory();
    }

    public function testCreateFromResponse()
    {
        $this->restResponse
            ->expects(static::once())
            ->method('json')
            ->willReturn(self::APRUVE_INVOICE);

        $actual = $this->factory->createFromResponse($this->restResponse);

        static::assertEquals(self::APRUVE_INVOICE, $actual->getData());
    }
}
