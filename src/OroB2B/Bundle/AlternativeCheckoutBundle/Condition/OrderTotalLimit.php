<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Condition;

use Oro\Component\ConfigExpression\Condition\AbstractComparison;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class OrderTotalLimit extends AbstractComparison
{
    const NAME = 'less_order_total_limit';

    /**
     * @var TotalProcessorProvider
     */
    protected $totalsProvider;

    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @param TotalProcessorProvider $totalsProvider
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     */
    public function __construct(
        TotalProcessorProvider $totalsProvider,
        CheckoutLineItemsManager $checkoutLineItemsManager
    ) {
        $this->totalsProvider = $totalsProvider;
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        return $this->doCompare(
            $this->resolveValue($context, $this->left),
            $this->resolveValue($context, $this->right)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function doCompare($left, $right)
    {
        $orderLineItems = $this->checkoutLineItemsManager->getData($left);
        $order = new Order();
        $order->setLineItems($orderLineItems);

        $orderTotalAmount = $this->totalsProvider->enableRecalculation()->getTotal($order)->getAmount();

        return $orderTotalAmount <= $right;
    }
}
