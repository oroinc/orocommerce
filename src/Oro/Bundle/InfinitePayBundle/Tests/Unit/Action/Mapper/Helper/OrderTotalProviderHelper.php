<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\Mapper\Helper;

use Oro\Bundle\InfinitePayBundle\Action\Provider\OrderTotalProviderInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\OrderTotal;

/**
 * {@inheritdoc}
 */
class OrderTotalProviderHelper extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    protected $currency = 'EUR';

    /** @var int */
    protected $totalGross = 1190;

    /** @var int */
    protected $totalNet = 1000;
    protected $rebateGross = 0;
    protected $rebaseNet = 0;
    protected $shippingPriceGross = 500;
    protected $shippingPriceNet = 420;
    protected $initDateTime = '20170101 12:00:00';

    /**
     * @return OrderTotalProviderInterface
     */
    public function getOrderTotalProvider()
    {
        $orderTotalProvider = $this
            ->getMockBuilder('Oro\Bundle\InfinitePayBundle\Action\Provider\OrderTotalProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $orderData = (new OrderTotal())
            ->setTrsCurrency($this->currency)
            ->setTrsAmtGross($this->totalGross)
            ->setTrsAmtNet($this->totalNet)
            ->setPayType(OrderTotalProviderInterface::PAY_TYPE_INVOICE)
            ->setRabateGross($this->rebateGross)
            ->setRabateNet($this->rebaseNet)
            ->setShippingPriceGross($this->shippingPriceGross)
            ->setShippingPriceNet($this->shippingPriceNet)
            ->setTermsAccepted('1')
            ->setTrsDt($this->initDateTime)
            ->setTotalGrossCalcMethod(OrderTotalProviderInterface::TOTAL_CALC_B2B_TAX_PER_ITEM);

        $orderTotalProvider
            ->method('getOrderTotal')
            ->willReturn($orderData);

        return $orderTotalProvider;
    }
}
