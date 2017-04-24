<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Client\Request\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Model\Invoice\ApruveInvoiceInterface;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;
use Oro\Bundle\ApruveBundle\Client\Request\Invoice\CreateInvoiceRequestFactory;

class CreateInvoiceRequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    const DATA = ['sampleData' => ['foo' => 'bar']];
    const APRUVE_ORDER_ID = 'sampleApruveOrderId';
    const URI = '/orders/sampleApruveOrderId/invoices';

    /**
     * @var CreateInvoiceRequestFactory
     */
    private $factory;

    /**
     * @var ApruveInvoiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestData;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->requestData = $this->createMock(ApruveInvoiceInterface::class);
        $this->factory = new CreateInvoiceRequestFactory();
    }

    public function testCreate()
    {
        $request = new ApruveRequest(CreateInvoiceRequestFactory::METHOD, self::URI, $this->requestData);
        $this->requestData
            ->method('getApruveOrderId')
            ->willReturn(self::APRUVE_ORDER_ID);
        $actual = $this->factory->create($this->requestData);

        static::assertEquals($request, $actual);
    }
}
