<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Method\Provider\InvoiceNumberGeneratorInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData;
use Oro\Bundle\OrderBundle\Entity\Order;

class InvoiceDataProvider implements InvoiceDataProviderInterface
{
    /**
     * @var InvoiceNumberGeneratorInterface
     */
    protected $invoiceNumberGenerator;

    public function __construct(InvoiceNumberGeneratorInterface $numberGenerator)
    {
        $this->invoiceNumberGenerator = $numberGenerator;
    }

    /**
     * @inheritdoc
     */
    public function getInvoiceData(Order $order, InfinitePayConfigInterface $config, $delayInDays = 0)
    {
        $duePeriod = $config->getInvoiceDuePeriod();
        $deliveryDays = $config->getShippingDuration();

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
