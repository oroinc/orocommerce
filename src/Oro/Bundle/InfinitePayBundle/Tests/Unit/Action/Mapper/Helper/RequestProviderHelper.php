<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper\Helper;

use Oro\Bundle\InfinitePayBundle\Action\Provider\ClientDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProviderInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ClientData;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\InvoiceData;

/**
 * {@inheritdoc}
 */
class RequestProviderHelper extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $clientRef
     * @param string $securityCode
     *
     * @return ClientDataProviderInterface
     */
    public function getClientDataProvider($clientRef = 'ref_test', $securityCode = 'security_code_test')
    {
        $clientDataProvider = $this
            ->getMockBuilder('Oro\Bundle\InfinitePayBundle\Action\Provider\ClientDataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $clientData = (new clientData())
            ->setClientRef($clientRef)
            ->setSecurityCd($securityCode)
        ;

        $clientDataProvider
            ->expects($this->any())
            ->method('getClientData')
            ->willReturn($this->returnValue($clientData));

        return $clientDataProvider;
    }

    /**
     * @param string $invoiceId
     * @param int    $duePeriod
     * @param int    $deliveryDays
     * @param int    $delayInDays
     *
     * @return InvoiceDataProviderInterface
     */
    public function getInvoiceDataProvider($invoiceId = '', $duePeriod = 30, $deliveryDays = 14, $delayInDays = 0)
    {
        $invoiceDataProvider = $this
            ->getMockBuilder('Oro\Bundle\InfinitePayBundle\Action\Provider\InvoiceDataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $invoiceData = (new InvoiceData())
            ->setInvoiceId($invoiceId)
            ->setInvoiceDate((new \DateTime())->format('Ymd'))
            ->setDueDate($this->getDateInDays($duePeriod))
            ->setPaymentTerms('1')
            ->setDelayInDays((string) $delayInDays)
            ->setDeliveryDate($this->getDateInDays($deliveryDays))
        ;

        $invoiceDataProvider
            ->expects($this->once())
            ->method('getInvoiceData')
            ->willReturn($this->returnValue($invoiceData));

        return $invoiceDataProvider;
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
