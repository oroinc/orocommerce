<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper;

use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProvider;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfig;
use Oro\Bundle\InfinitePayBundle\Method\Provider\InvoiceNumberGenerator;
use Oro\Bundle\InfinitePayBundle\Method\Provider\InvoiceNumberGeneratorInterface;
use Oro\Bundle\OrderBundle\Entity\Order;

/**
 * {@inheritdoc}
 */
class InvoiceDataProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $duePeriod = 30;
    protected $shippingDuration = 21;
    protected $identifier = 'test_identifier';
    /** @var InvoiceNumberGeneratorInterface */
    protected $invoiceNumberGenerator;

    /** @var InfinitePayConfig */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->invoiceNumberGenerator = new InvoiceNumberGenerator();

        $this->config = new InfinitePayConfig(
            [
                InfinitePayConfig::INVOICE_DUE_PERIOD_KEY => $this->duePeriod,
                InfinitePayConfig::INVOICE_SHIPPING_DURATION_KEY => $this->shippingDuration
            ]
        );
    }

    public function testGetInvoiceData()
    {
        $invoiceData = new InvoiceDataProvider($this->invoiceNumberGenerator);
        $order = (new Order())->setIdentifier($this->identifier);
        $invoiceData = $invoiceData->getInvoiceData($order, $this->config);
        $this->assertEquals($this->identifier, $invoiceData->getInvoiceId());
        $this->assertEquals(0, $invoiceData->getDelayInDays());

        $expectedInvoiceDate = new \DateTime();
        $this->assertEquals($expectedInvoiceDate->format('Ymd'), $invoiceData->getInvoiceDate());

        $expectedDueDate = (new \DateTime())->modify(sprintf('+ %s days', $this->duePeriod));
        $this->assertEquals($expectedDueDate->format('Ymd'), $invoiceData->getDueDate());
        $this->assertEquals($this->duePeriod, $invoiceData->getPaymentTerms());

        $expectedDeliveryDate = (new \DateTime())->modify(sprintf('+ %s days', $this->shippingDuration));
        $this->assertEquals($expectedDeliveryDate->format('Ymd'), $invoiceData->getDeliveryDate());
    }
}
