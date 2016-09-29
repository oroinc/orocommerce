<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Method\Provider\InvoiceNumberGeneratorInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData;
use Oro\Bundle\OrderBundle\Entity\Order;

class InvoiceDataProvider implements InvoiceDataProviderInterface
{
    /** @var InfinitePayConfigInterface */
    protected $config;

    /**
     * @var InvoiceNumberGeneratorInterface
     */
    protected $invoiceNumberGenerator;

    public function __construct(InfinitePayConfigInterface $config, InvoiceNumberGeneratorInterface $numberGenerator)
    {
        $this->config = $config;
        $this->invoiceNumberGenerator = $numberGenerator;
    }

    /**
     * @param Order $order
     * @param int   $delayInDays
     *
     * @return InvoiceData
     */
    public function getInvoiceData(Order $order, $delayInDays = 0)
    {
        $duePeriod = $this->config->getInvoiceDuePeriod();
        $deliveryDays = $this->config->getShippingDuration();

        $invoiceData = new InvoiceData();
        $invoiceData
            ->setInvoiceId($this->invoiceNumberGenerator->getInvoiceNumberFromOrder($order))
            ->setInvoiceDate((new \DateTime())->format('Ymd'))
            ->setDueDate($this->getDateInDays($duePeriod))
            ->setPaymentTerms($duePeriod)
            ->setDelayInDays((string) $delayInDays)
            ->setDeliveryDate($this->getDateInDays($deliveryDays))
        ;

        return $invoiceData;
    }

    /**
     * @param int $daysUntilDue
     *
     * @return string
     */
    private function getDateInDays($daysUntilDue)
    {
        return (new \DateTime())
            ->modify(sprintf('+ %s days', $daysUntilDue))
            ->format('Ymd')
            ;
    }
}
